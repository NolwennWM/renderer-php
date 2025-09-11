<?php
// filepath: c:\Users\Nolwenn\Documents\dev\Dice-of-Developper\app\src\View\Renderer.php
namespace NWM\Renderer;

class Renderer
{
    private $default_html;
    private $lang;

    public function __construct(string $default_html = "", string $lang = "fr")
    {
        $this->default_html = $default_html;
        $this->lang = $lang;
    }

    public function render(string $file, array $data = [], array $toRender = []): void
    {
        if (file_exists($file)) {
            ob_start();
            extract($data, EXTR_SKIP);
            require $file;
            $content = ob_get_clean();

            $toRender["lang"] ??= $this->lang;
            $toRender["title"] ??= "Document";

            if (!empty($this->default_html)) {
                $content = preg_replace('/\{\{\s*content\s*\}\}/', $content, $this->default_html);
            }
            foreach ($toRender as $key => $value) {
                if (is_array($value)) $value = implode('<br>', $value);
                $content = preg_replace('/\{\{\s*' . $key . '\s*\}\}/', $value, $content);
            }
            $content = preg_replace('/\{\{\s*.+\s*\}\}/', "", $content);

            echo $content;
        } else {
            require __DIR__ . "/404.php";
        }
    }
}