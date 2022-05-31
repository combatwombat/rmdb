<?php

namespace RTF;

class Helper {

    public function getFileLines($fileName) {
        $file = new \SplFileObject($fileName, 'r');
        $file->seek(PHP_INT_MAX);
        return $file->key();
    }

    public function formatTime($seconds) {
        if ($seconds < 60) {
            return sprintf("%02ds", $seconds);
        }
        if ($seconds < 3600) {
            $minutes = ($seconds / 60) % 60;
            $s = $seconds % 60;
            return sprintf("%02dm%02ds", $minutes, $s);
        }

        $H = floor($seconds / 3600);
        $i = ($seconds / 60) % 60;
        $s = $seconds % 60;

        return sprintf("%02dh%02dm%02ds", $H, $i, $s);
    }


}