<?php
function loadJson($path) {
    return json_decode(file_get_contents($path), true);
}

function saveJson($path, $data) {
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}