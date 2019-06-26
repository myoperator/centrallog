<?php
namespace MyOperator;

use \LoggerRenderer;
class DetailLogRenderer implements LoggerRenderer {
    public function render($input) {
        if(is_array($input)) {
            $input = $this->array_map_r(array($this, 'process'), $input);
            $input = json_encode($input);
        }
        if($input instanceof \Exception) {
            $input = array('kind' => 'exception', 'message'=> $input->getMessage(), 'code' => $input->getCode(), 'trace' => $v->getTrace());
            $input = json_encode($input);
        }

        try {
            return (string) $input;
        } catch (\Exception $e) {
            return $input;
        }
    }

    function array_map_r($callback, $input) {
        $output= Array();
        foreach ($input as $key => $data) {
            if (is_array($data)) {
                $output[$key] = $this->array_map_r($callback, $data);
            } else {
                $output[$key] = $callback($data);
            }
        }
        return $output;
    }

    private function process($v)
    {
        if($v instanceof \Exception) {
            return array('code' => $v->getCode(), 'message' => $v->getMessage(), 'trace' => $v->getTrace());
        }
        if(!$v || empty($v) || is_null($v)) {
            $v = '';
        }
        return $v;
    }
}
