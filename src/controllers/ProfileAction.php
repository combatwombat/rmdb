<?php

class ProfileAction extends \RTF\Controller {

    public function execute() {
        $this->auth();
        echo "in ProfileAction@execute() :)";
    }
}