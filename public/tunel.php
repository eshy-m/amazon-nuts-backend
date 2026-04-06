<?php
$targetFolder = __DIR__.'/../storage/app/public';
$linkFolder = __DIR__.'/storage';

if(symlink($targetFolder, $linkFolder)) {
    echo "¡Túnel creado con éxito a lo hacker!";
} else {
    echo "Ya existe el túnel o hubo un error.";
}
?>