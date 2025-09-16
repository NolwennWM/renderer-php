<?php
namespace NWM\Renderer;

/**
 * Class for managing custom functions and filters.
 */
class FunctionRegistry
{
    private array $functions = [];
    private array $filters = [];

    public function __construct()
    {
        // Register a default 'raw' filter that outputs the value without escaping.
        $this->registerFilter('raw', [$this, 'filterRaw']);
        // Register a default 'currentYear' function that returns the current year.
        $this->registerFunction('currentYear', [$this, 'currentYear']);
    }
    /**
     * Register a custom function.
     *
     * @param string $name name of the function
     * @param callable $function the function to register
     * @return void
     */
    public function registerFunction(string $name, callable $function): void
    {
        $this->functions[$name] = $function;
    }
    /**
     * Register a custom filter.
     *
     * @param string $name name of the filter
     * @param callable $filter the filter to register
     * @return void
     */
    public function registerFilter(string $name, callable $filter): void
    {
        $this->filters[$name] = $filter;
    }
    /**
     * Get a registered function by name.
     *
     * @param string $name name of the function
     * @return callable|null
     */
    public function getFunction(string $name): ?callable
    {
        return $this->functions[$name] ?? null;
    }
    /**
     * Get a registered filter by name.
     *
     * @param string $name name of the filter
     * @return callable|null
     */
    public function getFilter(string $name): ?callable
    {
        return $this->filters[$name] ?? null;
    }
    /**
     * Execute a registered function by name.
     *
     * @param string $name name of the function
     * @param array $args arguments to pass to the function
     * @return mixed
     */
    public function executeFunction(string $name, array $args = []): mixed
    {
        $function = $this->getFunction($name);
        return $function ? $function(...$args) : null;
    }
    /**
     * Execute a registered filter by name.
     *
     * @param string $name name of the filter
     * @param array $value value to pass to the filter
     * @return mixed
     */
    public function executeFilter(string $name, array $value): mixed
    {
        $filter = $this->getFilter($name);
        return $filter ? $filter(...$value) : null;
    }
    /**
     * Output a raw value without escaping.
     *
     * @param string $value the raw value to output
     * @return string
     */
    public function filterRaw(string $value): string
    {
        return $value;
    }
    /**
     * Function to get the current year.
     *
     * @return int the current year
     */
    public function currentYear(): int
    {
        return (int)date('Y');
    }
}