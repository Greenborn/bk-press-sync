<?php
/*
Plugin Name: BkSyncGreen
Description: Plugin para versionar los cambios realizados en todas las tablas existentes en la instalación de WordPress.
Version: 1.0.0
Author: Greenborn
*/
// Punto de entrada del plugin

// --- CREACIÓN DE LA TABLA DE VERSIONADO AL ACTIVAR EL PLUGIN ---
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

// --- FUNCIÓN AUXILIAR PARA EVITAR VERSIONADO EN TABLAS DEL PLUGIN ---
function bksyncgreen_es_tabla_plugin($tabla) {
    global $wpdb;
    $tablas_plugin = array(
        $wpdb->prefix . 'bksyncgreen_versions'
    );
    return in_array($tabla, $tablas_plugin);
}

// --- FUNCIÓN AUXILIAR PARA COMPARAR DATOS Y EVITAR VERSIONES DUPLICADAS ---
function bksyncgreen_datos_son_iguales($datos_anteriores, $datos_nuevos) {
    // Normalizar y comparar los datos
    $anteriores_normalizados = maybe_unserialize($datos_anteriores);
    $nuevos_normalizados = maybe_unserialize($datos_nuevos);
    
    // Si ambos son arrays u objetos, comparar serializados
    if (is_array($anteriores_normalizados) && is_array($nuevos_normalizados)) {
        return serialize($anteriores_normalizados) === serialize($nuevos_normalizados);
    }
    
    // Comparación directa para valores simples
    return $datos_anteriores === $datos_nuevos;
}

// --- MODIFICAR LOS REGISTROS PARA EVITAR VERSIONES DUPLICADAS ---

// save_post
add_action('save_post', 'bksyncgreen_registrar_cambio_post', 10, 3);
function bksyncgreen_registrar_cambio_post($post_ID, $post, $update) {
    global $wpdb;
    $tabla = $wpdb->posts;
    if (bksyncgreen_es_tabla_plugin($tabla)) return;
    
    $tabla_versionado = $wpdb->prefix . 'bksyncgreen_versions';
    $usuario_id = get_current_user_id();
    $fecha = current_time('mysql');
    $operacion = $update ? 'update' : 'insert';
    $datos_nuevos = maybe_serialize($post->to_array());
    $datos_anteriores = '';
    
    if ($update) {
        $post_anterior = get_post($post_ID);
        if ($post_anterior) {
            $datos_anteriores = maybe_serialize((array)$post_anterior);
        }
    }
    
    // Verificar si los datos son diferentes antes de registrar
    if (!bksyncgreen_datos_son_iguales($datos_anteriores, $datos_nuevos)) {
        $wpdb->insert(
            $tabla_versionado,
            array(
                'tabla' => $tabla,
                'operacion' => $operacion,
                'datos_anteriores' => $datos_anteriores,
                'datos_nuevos' => $datos_nuevos,
                'usuario_id' => $usuario_id,
                'fecha' => $fecha
            )
        );
    }
}

// profile_update
add_action('profile_update', 'bksyncgreen_registrar_cambio_usuario', 10, 2);
function bksyncgreen_registrar_cambio_usuario($user_id, $old_user_data) {
    global $wpdb;
    $tabla = $wpdb->users;
    if (bksyncgreen_es_tabla_plugin($tabla)) return;
    
    $tabla_versionado = $wpdb->prefix . 'bksyncgreen_versions';
    $usuario_id = get_current_user_id();
    $fecha = current_time('mysql');
    $nuevo_usuario = get_userdata($user_id);
    $datos_nuevos = maybe_serialize((array)$nuevo_usuario->data);
    $datos_anteriores = maybe_serialize((array)$old_user_data);
    
    // Verificar si los datos son diferentes antes de registrar
    if (!bksyncgreen_datos_son_iguales($datos_anteriores, $datos_nuevos)) {
        $wpdb->insert(
            $tabla_versionado,
            array(
                'tabla' => $tabla,
                'operacion' => 'update',
                'datos_anteriores' => $datos_anteriores,
                'datos_nuevos' => $datos_nuevos,
                'usuario_id' => $usuario_id,
                'fecha' => $fecha
            )
        );
    }
}

// updated_option
add_action('updated_option', 'bksyncgreen_registrar_cambio_opcion', 10, 3);
function bksyncgreen_registrar_cambio_opcion($option, $old_value, $value) {
    global $wpdb;
    $tabla = $wpdb->options;
    if (bksyncgreen_es_tabla_plugin($tabla)) return;
    
    $tabla_versionado = $wpdb->prefix . 'bksyncgreen_versions';
    $usuario_id = get_current_user_id();
    $fecha = current_time('mysql');
    $datos_anteriores = maybe_serialize($old_value);
    $datos_nuevos = maybe_serialize($value);
    
    // Verificar si los datos son diferentes antes de registrar
    if (!bksyncgreen_datos_son_iguales($datos_anteriores, $datos_nuevos)) {
        $wpdb->insert(
            $tabla_versionado,
            array(
                'tabla' => $tabla,
                'operacion' => 'update',
                'datos_anteriores' => $datos_anteriores,
                'datos_nuevos' => $datos_nuevos,
                'usuario_id' => $usuario_id,
                'fecha' => $fecha
            )
        );
    }
}

// Incluir el archivo de administración del plugin
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin/bksyncgreen-admin.php';
} 