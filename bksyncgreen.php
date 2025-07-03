<?php
/**
 * Plugin Name: BkSyncGreen
 * Description: Sistema de versionado de cambios en base de datos similar a Git
 * Version: 1.0.0
 * Author: Greenborn
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('BKSYNCGREEN_VERSION', '1.0.0');
define('BKSYNCGREEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BKSYNCGREEN_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Crear tablas al activar el plugin
register_activation_hook(__FILE__, 'bksyncgreen_activar_plugin');
function bksyncgreen_activar_plugin() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Tabla de versiones
    $tabla_versiones = $wpdb->prefix . 'bksyncgreen_versions';
    $sql_versiones = "CREATE TABLE $tabla_versiones (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nombre varchar(255) NOT NULL,
        descripcion text,
        usuario_id bigint(20) NOT NULL,
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        fecha_version datetime NULL,
        estado enum('open', 'closed') DEFAULT 'open',
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Tabla de commits
    $tabla_commits = $wpdb->prefix . 'bksyncgreen_commits';
    $sql_commits = "CREATE TABLE $tabla_commits (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        id_version mediumint(9) NOT NULL,
        descripcion text NOT NULL,
        usuario_id bigint(20) NOT NULL,
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        fecha_commit datetime NULL,
        estado enum('pending', 'committed') DEFAULT 'pending',
        PRIMARY KEY (id),
        FOREIGN KEY (id_version) REFERENCES $tabla_versiones(id) ON DELETE CASCADE
    ) $charset_collate;";
    
    // Tabla de cambios
    $tabla_cambios = $wpdb->prefix . 'bksyncgreen_changes';
    $sql_cambios = "CREATE TABLE $tabla_cambios (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        id_commit mediumint(9) NOT NULL,
        tabla varchar(255) NOT NULL,
        registro_id bigint(20) NOT NULL,
        operacion enum('insert', 'update', 'delete') NOT NULL,
        datos_anteriores longtext,
        datos_nuevos longtext,
        usuario_id bigint(20) NOT NULL,
        fecha datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (id_commit) REFERENCES $tabla_commits(id) ON DELETE CASCADE
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_versiones);
    dbDelta($sql_commits);
    dbDelta($sql_cambios);
    
    // Crear versión inicial
    $version_id = $wpdb->insert(
        $tabla_versiones,
        array(
            'nombre' => 'Versión inicial',
            'descripcion' => 'Versión creada automáticamente al activar el plugin',
            'usuario_id' => get_current_user_id() ?: 1,
            'estado' => 'open'
        )
    );
    
    if ($version_id) {
        // Crear commit inicial
        $wpdb->insert(
            $tabla_commits,
            array(
                'id_version' => $wpdb->insert_id,
                'descripcion' => 'Commit inicial',
                'usuario_id' => get_current_user_id() ?: 1,
                'estado' => 'pending'
            )
        );
    }
    
    // Configuración por defecto
    add_option('bksyncgreen_idioma', 'es');
}

// Obtener versión abierta actual
function bksyncgreen_get_version_abierta() {
    global $wpdb;
    $tabla_versiones = $wpdb->prefix . 'bksyncgreen_versions';
    
    return $wpdb->get_row("
        SELECT * FROM $tabla_versiones 
        WHERE estado = 'open' 
        ORDER BY fecha_creacion DESC 
        LIMIT 1
    ");
}

// Obtener commit pendiente actual
function bksyncgreen_get_commit_pendiente() {
    global $wpdb;
    $tabla_commits = $wpdb->prefix . 'bksyncgreen_commits';
    
    return $wpdb->get_row("
        SELECT * FROM $tabla_commits 
        WHERE estado = 'pending' 
        ORDER BY fecha_creacion DESC 
        LIMIT 1
    ");
}

// Crear nueva versión
function bksyncgreen_crear_version($nombre, $descripcion = '') {
    global $wpdb;
    $tabla_versiones = $wpdb->prefix . 'bksyncgreen_versions';
    
    // Cerrar versión anterior si existe
    $wpdb->update(
        $tabla_versiones,
        array('estado' => 'closed', 'fecha_version' => current_time('mysql')),
        array('estado' => 'open')
    );
    
    // Crear nueva versión
    $resultado = $wpdb->insert(
        $tabla_versiones,
        array(
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'usuario_id' => get_current_user_id() ?: 1,
            'estado' => 'open'
        )
    );
    
    if ($resultado) {
        // Crear commit inicial para la nueva versión
        $wpdb->insert(
            $wpdb->prefix . 'bksyncgreen_commits',
            array(
                'id_version' => $wpdb->insert_id,
                'descripcion' => 'Commit inicial de ' . $nombre,
                'usuario_id' => get_current_user_id() ?: 1,
                'estado' => 'pending'
            )
        );
        return true;
    }
    
    return false;
}

// Cerrar versión
function bksyncgreen_cerrar_version($version_id) {
    global $wpdb;
    $tabla_versiones = $wpdb->prefix . 'bksyncgreen_versions';
    
    return $wpdb->update(
        $tabla_versiones,
        array('estado' => 'closed', 'fecha_version' => current_time('mysql')),
        array('id' => $version_id, 'estado' => 'open')
    );
}

// Crear commit
function bksyncgreen_crear_commit($descripcion) {
    global $wpdb;
    $tabla_commits = $wpdb->prefix . 'bksyncgreen_commits';
    
    // Obtener versión abierta
    $version = bksyncgreen_get_version_abierta();
    if (!$version) {
        return false;
    }
    
    // Confirmar commit anterior si existe
    $commit_anterior = bksyncgreen_get_commit_pendiente();
    if ($commit_anterior) {
        $wpdb->update(
            $tabla_commits,
            array('estado' => 'committed', 'fecha_commit' => current_time('mysql')),
            array('id' => $commit_anterior->id)
        );
    }
    
    // Crear nuevo commit
    return $wpdb->insert(
        $tabla_commits,
        array(
            'id_version' => $version->id,
            'descripcion' => $descripcion,
            'usuario_id' => get_current_user_id() ?: 1,
            'estado' => 'pending'
        )
    );
}

// Verificar si una tabla pertenece al plugin
function bksyncgreen_es_tabla_plugin($tabla) {
    $tablas_plugin = array(
        'bksyncgreen_versions',
        'bksyncgreen_commits',
        'bksyncgreen_changes'
    );
    
    foreach ($tablas_plugin as $tabla_plugin) {
        if (strpos($tabla, $tabla_plugin) !== false) {
            return true;
        }
    }
    
    return false;
}

// Comparar datos
function bksyncgreen_datos_son_iguales($datos_anteriores, $datos_nuevos) {
    return serialize($datos_anteriores) === serialize($datos_nuevos);
}

// Registrar cambio en posts
function bksyncgreen_registrar_cambio_post($post_ID, $post, $update) {
    if (wp_is_post_revision($post_ID) || wp_is_post_autosave($post_ID)) {
        return;
    }
    
    global $wpdb;
    $tabla_cambios = $wpdb->prefix . 'bksyncgreen_changes';
    
    // Obtener commit pendiente
    $commit = bksyncgreen_get_commit_pendiente();
    if (!$commit) {
        return;
    }
    
    $tabla = $wpdb->posts;
    $datos_nuevos = (array) $post;
    
    if ($update) {
        // Actualización
        $datos_anteriores = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE ID = %d", $post_ID), ARRAY_A);
        
        if (!bksyncgreen_datos_son_iguales($datos_anteriores, $datos_nuevos)) {
            $wpdb->insert(
                $tabla_cambios,
                array(
                    'id_commit' => $commit->id,
                    'tabla' => $tabla,
                    'registro_id' => $post_ID,
                    'operacion' => 'update',
                    'datos_anteriores' => json_encode($datos_anteriores),
                    'datos_nuevos' => json_encode($datos_nuevos),
                    'usuario_id' => get_current_user_id() ?: 1
                )
            );
        }
    } else {
        // Inserción
        $wpdb->insert(
            $tabla_cambios,
            array(
                'id_commit' => $commit->id,
                'tabla' => $tabla,
                'registro_id' => $post_ID,
                'operacion' => 'insert',
                'datos_nuevos' => json_encode($datos_nuevos),
                'usuario_id' => get_current_user_id() ?: 1
            )
        );
    }
}

// Registrar cambio en usuarios
function bksyncgreen_registrar_cambio_usuario($user_id, $old_user_data) {
    global $wpdb;
    $tabla_cambios = $wpdb->prefix . 'bksyncgreen_changes';
    
    $commit = bksyncgreen_get_commit_pendiente();
    if (!$commit) {
        return;
    }
    
    $tabla = $wpdb->users;
    $datos_nuevos = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE ID = %d", $user_id), ARRAY_A);
    
    if ($old_user_data) {
        // Actualización
        if (!bksyncgreen_datos_son_iguales($old_user_data, $datos_nuevos)) {
            $wpdb->insert(
                $tabla_cambios,
                array(
                    'id_commit' => $commit->id,
                    'tabla' => $tabla,
                    'registro_id' => $user_id,
                    'operacion' => 'update',
                    'datos_anteriores' => json_encode($old_user_data),
                    'datos_nuevos' => json_encode($datos_nuevos),
                    'usuario_id' => get_current_user_id() ?: 1
                )
            );
        }
    } else {
        // Inserción
        $wpdb->insert(
            $tabla_cambios,
            array(
                'id_commit' => $commit->id,
                'tabla' => $tabla,
                'registro_id' => $user_id,
                'operacion' => 'insert',
                'datos_nuevos' => json_encode($datos_nuevos),
                'usuario_id' => get_current_user_id() ?: 1
            )
        );
    }
}

// Registrar cambio en opciones
function bksyncgreen_registrar_cambio_opcion($option, $old_value, $value) {
    if (bksyncgreen_es_tabla_plugin($option)) {
        return;
    }
    
    global $wpdb;
    $tabla_cambios = $wpdb->prefix . 'bksyncgreen_changes';
    
    $commit = bksyncgreen_get_commit_pendiente();
    if (!$commit) {
        return;
    }
    
    $tabla = $wpdb->options;
    
    if ($old_value !== false) {
        // Actualización
        if ($old_value !== $value) {
            $wpdb->insert(
                $tabla_cambios,
                array(
                    'id_commit' => $commit->id,
                    'tabla' => $tabla,
                    'registro_id' => 0,
                    'operacion' => 'update',
                    'datos_anteriores' => json_encode(array('option_name' => $option, 'option_value' => $old_value)),
                    'datos_nuevos' => json_encode(array('option_name' => $option, 'option_value' => $value)),
                    'usuario_id' => get_current_user_id() ?: 1
                )
            );
        }
    } else {
        // Inserción
        $wpdb->insert(
            $tabla_cambios,
            array(
                'id_commit' => $commit->id,
                'tabla' => $tabla,
                'registro_id' => 0,
                'operacion' => 'insert',
                'datos_nuevos' => json_encode(array('option_name' => $option, 'option_value' => $value)),
                'usuario_id' => get_current_user_id() ?: 1
            )
        );
    }
}

// Revertir commit
function bksyncgreen_revertir_commit($commit_id) {
    global $wpdb;
    $tabla_cambios = $wpdb->prefix . 'bksyncgreen_changes';
    $tabla_commits = $wpdb->prefix . 'bksyncgreen_commits';
    
    // Verificar que el commit existe y está confirmado
    $commit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_commits WHERE id = %d AND estado = 'committed'", $commit_id));
    if (!$commit) {
        return false;
    }
    
    // Obtener cambios del commit
    $cambios = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tabla_cambios WHERE id_commit = %d ORDER BY id DESC", $commit_id));
    
    $wpdb->query('START TRANSACTION');
    
    try {
        foreach ($cambios as $cambio) {
            $datos_anteriores = json_decode($cambio->datos_anteriores, true);
            $datos_nuevos = json_decode($cambio->datos_nuevos, true);
            
            switch ($cambio->operacion) {
                case 'insert':
                    // Revertir inserción = eliminar
                    if ($cambio->tabla === $wpdb->posts) {
                        wp_delete_post($cambio->registro_id, true);
                    } elseif ($cambio->tabla === $wpdb->users) {
                        wp_delete_user($cambio->registro_id);
                    } elseif ($cambio->tabla === $wpdb->options) {
                        delete_option($datos_nuevos['option_name']);
                    }
                    break;
                    
                case 'update':
                    // Revertir actualización = restaurar datos anteriores
                    if ($cambio->tabla === $wpdb->posts) {
                        $wpdb->update($cambio->tabla, $datos_anteriores, array('ID' => $cambio->registro_id));
                    } elseif ($cambio->tabla === $wpdb->users) {
                        $wpdb->update($cambio->tabla, $datos_anteriores, array('ID' => $cambio->registro_id));
                    } elseif ($cambio->tabla === $wpdb->options) {
                        update_option($datos_anteriores['option_name'], $datos_anteriores['option_value']);
                    }
                    break;
                    
                case 'delete':
                    // Revertir eliminación = insertar
                    if ($cambio->tabla === $wpdb->posts) {
                        $wpdb->insert($cambio->tabla, $datos_anteriores);
                    } elseif ($cambio->tabla === $wpdb->users) {
                        $wpdb->insert($cambio->tabla, $datos_anteriores);
                    } elseif ($cambio->tabla === $wpdb->options) {
                        add_option($datos_anteriores['option_name'], $datos_anteriores['option_value']);
                    }
                    break;
            }
        }
        
        $wpdb->query('COMMIT');
        return true;
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('Error al revertir commit BkSyncGreen: ' . $e->getMessage());
        return false;
    }
}

// Reiniciar repositorio
function bksyncgreen_reiniciar_repositorio() {
    global $wpdb;
    
    $tabla_versiones = $wpdb->prefix . 'bksyncgreen_versions';
    $tabla_commits = $wpdb->prefix . 'bksyncgreen_commits';
    $tabla_cambios = $wpdb->prefix . 'bksyncgreen_changes';
    
    $wpdb->query('START TRANSACTION');
    
    try {
        // Eliminar todos los datos
        $wpdb->query("DELETE FROM $tabla_cambios");
        $wpdb->query("DELETE FROM $tabla_commits");
        $wpdb->query("DELETE FROM $tabla_versiones");
        
        // Reiniciar auto-increment
        $wpdb->query("ALTER TABLE $tabla_cambios AUTO_INCREMENT = 1");
        $wpdb->query("ALTER TABLE $tabla_commits AUTO_INCREMENT = 1");
        $wpdb->query("ALTER TABLE $tabla_versiones AUTO_INCREMENT = 1");
        
        // Crear versión inicial
        $wpdb->insert(
            $tabla_versiones,
            array(
                'nombre' => 'Versión inicial',
                'descripcion' => 'Versión creada automáticamente al reiniciar el repositorio',
                'usuario_id' => get_current_user_id() ?: 1,
                'estado' => 'open'
            )
        );
        
        $version_id = $wpdb->insert_id;
        
        // Crear commit inicial
        $wpdb->insert(
            $tabla_commits,
            array(
                'id_version' => $version_id,
                'descripcion' => 'Commit inicial',
                'usuario_id' => get_current_user_id() ?: 1,
                'estado' => 'pending'
            )
        );
        
        $wpdb->query('COMMIT');
        return true;
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('Error al reiniciar repositorio BkSyncGreen: ' . $e->getMessage());
        return false;
    }
}

// Hooks para registrar cambios
add_action('save_post', 'bksyncgreen_registrar_cambio_post', 10, 3);
add_action('profile_update', 'bksyncgreen_registrar_cambio_usuario', 10, 2);
add_action('user_register', 'bksyncgreen_registrar_cambio_usuario', 10, 2);
add_action('updated_option', 'bksyncgreen_registrar_cambio_opcion', 10, 3);
add_action('added_option', 'bksyncgreen_registrar_cambio_opcion', 10, 3);

// Incluir archivo de administración
if (is_admin()) {
    require_once BKSYNCGREEN_PLUGIN_PATH . 'admin/bksyncgreen-admin.php';
} 