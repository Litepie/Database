<?php
namespace Litepie\Database\Traits;

trait Initializer
{

    public function initializeModel()
    {
        $config = config($this->config) ?? [];

        foreach ($config as $key => $val) {
            if (property_exists(get_called_class(), $key)) {
                $this->$key = $val;
            }
            if (method_exists(get_called_class(), $key)) {
                $this->$key($val);
            }
        }
    }
}
