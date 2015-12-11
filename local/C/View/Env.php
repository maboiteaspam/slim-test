<?php
namespace C\View;

class Env {

    public $dateFormat;
    public function setDateFormat ($dateFormat) {
        $this->dateFormat = $dateFormat;
    }

    public function getDateFormat () {
        return $this->dateFormat;
    }

    public $timezone;
    public function setTimezone ($timezone) {
        $this->timezone = $timezone;
    }

    public function getTimezone () {
        return $this->timezone;
    }

    public $numberFormat;
    public function setNumberFormat ($numberFormat) {
        $this->numberFormat = $numberFormat;
    }

    public function getNumberFormat () {
        return $this->numberFormat;
    }

    public $charset;
    public function setCharset ($charset) {
        $this->charset = $charset;
    }

    public function getCharset () {
        return $this->charset;
    }
}