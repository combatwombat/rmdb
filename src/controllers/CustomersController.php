<?php

class CustomersController extends \RTF\Controller {

    public function list() {


        echo "hu";
        echo "host: " . $this->config('db/host'). "\n";
        print_r($this->db->get('timeslips', 1));
    }

    public function execute() {
        if (!$this->auth->isLoggedIn() || !$this->auth->hasAccess('admin')) {
            $this->auth->return403();
            return;
        }

        echo "in execute :)";
    }
}