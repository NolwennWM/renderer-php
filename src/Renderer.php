<?php
namespace NWM\Renderer;

/**
 * Main renderer class for rendering HTML templates with dynamic data.
 */
class Renderer
{
    private string $default_page_not_found;
    private string $root_path;
    private FunctionRegistry $functionRegistry;
    private array $dataToRender = [];

    /**
     * Constructor to initialize the renderer with default settings.
     *
     * @param string $default_html default HTML template
     * @param string $lang default language
     * @param string $default_title default title for the document
     * @param string $default_page_not_found path to the default 404 error page
     * @param string $root_path root path for resolving file paths
     */
    public function __construct(private string $default_html = "", private string $lang = "fr", private string $default_title = "Document", string $default_page_not_found = "404.php", string $root_path = __DIR__)
    {
        $this->default_page_not_found = $_ENV["DEFAULT_PAGE_NOT_FOUND"] ?? $default_page_not_found;
        $this->root_path = $_ENV["ROOT_PATH"] ?? $root_path;
        $this->functionRegistry = new FunctionRegistry();
    }

    /**
     * Render a template file with provided data and additional variables.
     *
     * @param string $file path to the template file
     * @param array $dataToRender associative array of data to render in the template
     * @param array $dataToVariable associative array of data to extract into variable for the template
     * @return void
     */
    public function render(string $file, array $dataToRender = [], array $dataToVariable = []): void
    {
        $this->dataToRender = $dataToRender;
        if (file_exists($file)) {
            ob_start();
            extract($dataToVariable, EXTR_SKIP);
            require $file;
            $content = ob_get_clean();

            $dataToRender["lang"] ??= $this->getLang();
            $dataToRender["title"] ??= $this->getDefaultTitle();

            $content = $this->renderVariables($content);
            $content = $this->renderFunctions($content);
            $content = $this->renderFilters($content);
            $content = $this->clearContent($content);

            echo $content;
        } else {
            $this->render404();
        }

    }
    /**
     * Render the 404 error page.
     *
     * @return void
     */
    private function render404(): void
    {
        http_response_code(404);
        $page404 = $this->root_path . '/' . $this->default_page_not_found;
        if (!file_exists($page404)) {
            echo "<h1>404 Not Found</h1><p>The requested page was not found.</p>";
            return;
        }
        require $this->root_path . '/' . $this->default_page_not_found;
    }
    /**
     * Render variables in the content by replacing placeholders with actual values.
     *
     * @param array $toRender associative array of variables to replace
     * @param string $content the content string with placeholders
     * @return string the content with variables replaced
     */
    private function renderVariables(string $content): string
    {
        $default_html = $this->getDefaultHtml();
        if (!empty($default_html)) {
            $content = preg_replace('/\{\{\s*content\s*\}\}/', $content, $default_html);
        }
        foreach ($this->dataToRender as $key => $value) {
            if (is_array($value)) $value = implode('<br>', $value);
            if (is_object($value)) $value = json_encode($value);
            $value = htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $content = preg_replace('/\{\{\s*' . $key . '\s*\}\}/', $value, $content);
        }
        return $content;
    }
    /**
     * Render functions in the content by replacing function calls with their results.
     *
     * @param string $content the content string with function calls
     * @return string the content with function calls replaced by their results
     */
    private function renderFunctions(string $content): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\((.*?)\)\s*\}\}/', [$this, 'replaceFunctionCallback'], $content);
    }
    /**
     * Render filters in the content by applying registered filters to variables.
     *
     * @param string $content the content string with filter calls
     * @return string the content with filter calls replaced by their results
     */
    private function renderFilters(string $content): string
    {
        // Syntaxe pipe : {{ variable | filter1 | filter2(arg) }}
        return preg_replace_callback('/\{\{\s*([^\|\}]+)((?:\s*\|\s*[^\|\}]+)+)\s*\}\}/', [$this, 'replaceFilterCallback'], $content);
    }
    /**
     * Clear any unreplaced placeholders in the content.
     *
     * @param string $content the content string
     * @return string the content with unreplaced placeholders removed
     */
    private function clearContent(string $content): string
    {
        return preg_replace('/\{\{\s*.+\s*\}\}/', "", $content);
    }
    /**
     * Callback function to replace function calls in the template.
     *
     * @param array $matches regex matches
     * @return string|null the result of the function call or the original match if not found
     */
    private function replaceFunctionCallback(array $matches): string|null
    {
        $functionName = $matches[1];
        $args = array_map([$this, 'replaceVariable'], explode(',', $matches[2]));

        
        $function = $this->functionRegistry->getFunction($functionName);
        if (is_callable($function)) {
            return $this->functionRegistry->executeFunction($functionName, $args);
        }
        return $matches[0]; 
    }
    /**
     * Callback function to replace filter calls in the template.
     *
     * @param array $matches regex matches
     * @return string|null the result of the filter call or the original match if not found
     */
    private function replaceFilterCallback(array $matches): string|null
    {
        $value = $this->replaceVariable($matches[1]);
        $pipes = preg_split('/\|/', $matches[2]);
        foreach ($pipes as $pipe) {
            $pipe = trim($pipe);
            if ($pipe === '') continue;
            if (preg_match('/^(\w+)\((.*?)\)$/', $pipe, $filterMatch)) {
                $filterName = $filterMatch[1];
                $args = array_map([$this, 'replaceVariable'], explode(',', $filterMatch[2]));
            } else {
                $filterName = $pipe;
                $args = [];
            }
            $filter = $this->functionRegistry->getFilter($filterName);
            if (is_callable($filter)) {
                $value = $this->functionRegistry->executeFilter($filterName, array_merge([$value], $args));
            }
        }
        return $value;
    }
    /**
     * Function to replace variable placeholders in the template.
     *
     * @param string $value the variable name
     * @return string|null the value of the variable or the original match if not found
     */
    private function replaceVariable(string $value): string|null
    {
        $varName = trim($value);
        return $this->dataToRender[$varName] ?? $value;
    }
    /**
     * Get the FunctionRegistry instance for registering and managing custom functions and filters.
     *
     * @return FunctionRegistry
     */
    public function getFunctionRegistry(): FunctionRegistry
    {
        return $this->functionRegistry;
    }
    /**
     * Get the current language setting.
     *
     * @return string
     */
    public function getLang(): string
    {
        return $this->lang;
    }
    /**
     * Set the language setting.
     *
     * @param string $lang
     * @return void
     */
    public function setLang(string $lang): void
    {
        $this->lang = $lang;
    }
    /**
     * Get the default HTML template.
     *
     * @return string
     */
    public function getDefaultHtml(): string
    {
        return $this->default_html;
    }
    /**
     * Set the default HTML template.
     *
     * @param string $default_html
     * @return void
     */
    public function setDefaultHtml(string $default_html): void
    {
        $this->default_html = $default_html;
    }
    /**
     * Get the default title for the document.
     *
     * @return string
     */    
    public function getDefaultTitle(): string
    {
        return $this->default_title;
    }
    /**
     * Set the default title for the document.
     *
     * @param string $default_title
     * @return void
     */
    public function setDefaultTitle(string $default_title): void
    {
        $this->default_title = $default_title;
    }
    
}