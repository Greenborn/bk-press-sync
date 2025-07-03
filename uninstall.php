<?php
// Si no es llamado desde WordPress, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}
// Aquí va la lógica de limpieza (eliminación de opciones, tablas, etc.) 