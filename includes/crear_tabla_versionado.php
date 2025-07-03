<?php
// Autor: Greenborn
// Script para crear las tablas de versionado al activar el plugin

function bksyncgreen_crear_tabla_versionado() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de versiones (nivel superior)
    $tabla_versiones = $wpdb->prefix . 'bksyncgreen_versions';
    $sql_versiones = "CREATE TABLE $tabla_versiones (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL,
        descripcion TEXT,
        estado ENUM('open','closed') DEFAULT 'open',
        usuario_id BIGINT UNSIGNED NOT NULL,
        fecha_creacion DATETIME NOT NULL,
        fecha_version DATETIME NULL,
        INDEX idx_usuario (usuario_id),
        INDEX idx_estado (estado),
        INDEX idx_fecha_creacion (fecha_creacion)
    ) $charset_collate;";

    // Tabla de commits (nivel medio)
    $tabla_commits = $wpdb->prefix . 'bksyncgreen_commits';
    $sql_commits = "CREATE TABLE $tabla_commits (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_version BIGINT UNSIGNED NULL,
        descripcion TEXT NOT NULL,
        estado ENUM('pending','committed') DEFAULT 'pending',
        usuario_id BIGINT UNSIGNED NOT NULL,
        fecha_creacion DATETIME NOT NULL,
        fecha_commit DATETIME NULL,
        INDEX idx_version (id_version),
        INDEX idx_usuario (usuario_id),
        INDEX idx_estado (estado),
        INDEX idx_fecha_creacion (fecha_creacion),
        FOREIGN KEY (id_version) REFERENCES $tabla_versiones(id) ON DELETE SET NULL
    ) $charset_collate;";

    // Tabla de cambios (nivel inferior)
    $tabla_cambios = $wpdb->prefix . 'bksyncgreen_changes';
    $sql_cambios = "CREATE TABLE $tabla_cambios (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_commit BIGINT UNSIGNED NOT NULL,
        tabla VARCHAR(100) NOT NULL,
        operacion ENUM('insert','update','delete') NOT NULL,
        datos_anteriores LONGTEXT,
        datos_nuevos LONGTEXT,
        usuario_id BIGINT UNSIGNED NOT NULL,
        fecha DATETIME NOT NULL,
        INDEX idx_commit (id_commit),
        INDEX idx_tabla (tabla),
        INDEX idx_usuario (usuario_id),
        INDEX idx_fecha (fecha),
        FOREIGN KEY (id_commit) REFERENCES $tabla_commits(id) ON DELETE CASCADE
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Crear las tablas en orden (versiones -> commits -> cambios)
    dbDelta($sql_versiones);
    dbDelta($sql_commits);
    dbDelta($sql_cambios);
}

register_activation_hook(__FILE__, 'bksyncgreen_crear_tabla_versionado'); 