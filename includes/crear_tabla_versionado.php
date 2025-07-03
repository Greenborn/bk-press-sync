<?php
// Autor: Greenborn
// Script para crear la tabla de versionado al activar el plugin

function bksyncgreen_crear_tabla_versionado() {
    global $wpdb;
    $tabla_versionado = $wpdb->prefix . 'bksyncgreen_versions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tabla_versionado (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tabla VARCHAR(255) NOT NULL,
        operacion ENUM('insert','update','delete') NOT NULL,
        datos_anteriores LONGTEXT,
        datos_nuevos LONGTEXT,
        usuario_id BIGINT UNSIGNED,
        fecha DATETIME NOT NULL
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'bksyncgreen_crear_tabla_versionado'); 