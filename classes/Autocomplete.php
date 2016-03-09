<?php
namespace Rhino\DataTable;

class Autocomplete {
    use \Rhino\Core\ModuleAccess;
    use \Rhino\Core\Renderer;

    protected $url;
    protected $inputName;
    protected $keys;

    public function render() {
        $autocomplete = new \Rhino\Core\Escaper\Wrapped($this);
        ob_start();
        require $this->getModule()->getRoot('/views/autocomplete.php');
        return ob_get_clean();
    }
    
    public function getUrl() {
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    public function getInputName() {
        return $this->inputName;
    }

    public function setInputName($inputName) {
        $this->inputName = $inputName;
        return $this;
    }

    public function getKeys() {
        return $this->keys;
    }

    public function setKeys($keys) {
        $this->keys = $keys;
        return $this;
    }

}
