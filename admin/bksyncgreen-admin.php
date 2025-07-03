<?php
// Archivo de administración para BkSyncGreen

// Agregar menú y submenús
add_action('admin_menu', 'bksyncgreen_admin_menu');
function bksyncgreen_admin_menu() {
    add_menu_page(
        'BkSyncGreen',
        'BkSyncGreen',
        'manage_options',
        'bksyncgreen',
        'bksyncgreen_versiones_page',
        'dashicons-backup',
        80
    );
    add_submenu_page('bksyncgreen', 'Versiones', 'Versiones', 'manage_options', 'bksyncgreen', 'bksyncgreen_versiones_page');
    add_submenu_page('bksyncgreen', 'Commits', 'Commits', 'manage_options', 'bksyncgreen-commits', 'bksyncgreen_commits_page');
    add_submenu_page('bksyncgreen', 'Configuración', 'Configuración', 'manage_options', 'bksyncgreen-config', 'bksyncgreen_config_page');
}

// Página de configuración
function bksyncgreen_config_page() {
    // Guardar idioma
    if (isset($_POST['bksyncgreen_save_config']) && check_admin_referer('bksyncgreen_config')) {
        $idioma = sanitize_text_field($_POST['idioma']);
        update_option('bksyncgreen_idioma', $idioma);
        echo '<div class="notice notice-success is-dismissible"><p>Idioma actualizado correctamente.</p></div>';
    }
    
    // Reiniciar repositorio
    if (isset($_POST['bksyncgreen_reiniciar_repositorio']) && check_admin_referer('bksyncgreen_reiniciar')) {
        if (!empty($_POST['confirmacion_reinicio']) && $_POST['confirmacion_reinicio'] === 'REINICIAR') {
            $ok = bksyncgreen_reiniciar_repositorio();
            if ($ok) {
                echo '<div class="notice notice-success is-dismissible"><p>Repositorio reiniciado correctamente.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Error al reiniciar el repositorio.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Debes escribir REINICIAR para confirmar.</p></div>';
        }
    }
    
    $idioma_actual = get_option('bksyncgreen_idioma', 'es');
    $idiomas = array(
        'es' => 'Español',
        'en' => 'English',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português'
    );
    
    echo '<div class="wrap">';
    echo '<h1>Configuración de BkSyncGreen</h1>';
    echo '<p>Configura las opciones básicas del plugin de versionado de base de datos.</p>';
    
    // Formulario de configuración
    echo '<div class="card" style="max-width: 600px; margin-bottom: 30px;">';
    echo '<h2>Configuración General</h2>';
    echo '<form method="post">';
    wp_nonce_field('bksyncgreen_config');
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="idioma">Idioma de la interfaz</label></th>';
    echo '<td><select id="idioma" name="idioma" class="regular-text">';
    foreach ($idiomas as $codigo => $nombre) {
        $selected = ($codigo === $idioma_actual) ? ' selected' : '';
        echo '<option value="' . esc_attr($codigo) . '"' . $selected . '>' . esc_html($nombre) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">Selecciona el idioma para la interfaz de administración del plugin.</p></td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit"><input type="submit" name="bksyncgreen_save_config" class="button-primary" value="Guardar configuración"></p>';
    echo '</form>';
    echo '</div>';
    
    // Formulario de reinicio
    echo '<div class="card" style="max-width: 600px; margin-bottom: 30px;">';
    echo '<h2>Reiniciar Repositorio</h2>';
    echo '<div class="notice notice-warning">';
    echo '<p><strong>⚠️ ADVERTENCIA:</strong> Esta acción eliminará permanentemente todas las versiones, commits y cambios registrados en el sistema. Esta acción no se puede deshacer.</p>';
    echo '</div>';
    echo '<form method="post" onsubmit="return confirm(\'¿Estás completamente seguro de que quieres reiniciar el repositorio? Esta acción eliminará permanentemente todos los datos y no se puede deshacer.\')">';
    wp_nonce_field('bksyncgreen_reiniciar');
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="confirmacion_reinicio">Confirmación</label></th>';
    echo '<td>';
    echo '<input type="text" id="confirmacion_reinicio" name="confirmacion_reinicio" class="regular-text" placeholder="Escribe REINICIAR para confirmar" />';
    echo '<p class="description">Para confirmar que quieres reiniciar el repositorio, escribe "REINICIAR" en el campo de arriba.</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit"><input type="submit" name="bksyncgreen_reiniciar_repositorio" class="button button-danger" value="Reiniciar Repositorio" style="background-color: #dc3545; border-color: #dc3545; color: white;"></p>';
    echo '</form>';
    echo '</div>';
    
    // Información del repositorio
    global $wpdb;
    $tabla_versiones = $wpdb->prefix . 'bksyncgreen_versions';
    $tabla_commits = $wpdb->prefix . 'bksyncgreen_commits';
    $tabla_cambios = $wpdb->prefix . 'bksyncgreen_changes';
    
    $total_versiones = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_versiones");
    $total_commits = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_commits");
    $total_cambios = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cambios");
    
    echo '<div class="card" style="max-width: 600px;">';
    echo '<h2>Información del Repositorio</h2>';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row">Versiones registradas</th>';
    echo '<td>' . $total_versiones . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row">Commits registrados</th>';
    echo '<td>' . $total_commits . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row">Cambios registrados</th>';
    echo '<td>' . $total_cambios . '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</div>';
    
    echo '</div>';
    
    // Estilos CSS
    echo '<style>
    .button-danger {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: white !important;
    }
    .button-danger:hover {
        background-color: #c82333 !important;
        border-color: #bd2130 !important;
        color: white !important;
    }
    .notice-warning {
        border-left-color: #ffc107;
        background-color: #fff3cd;
    }
    .card {
        background: white;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    </style>';
}

// Página de versiones
function bksyncgreen_versiones_page() {
    global $wpdb;
    
    // Procesar creación de versión
    if (isset($_POST['bksyncgreen_crear_version']) && isset($_POST['nombre_version'])) {
        if (check_admin_referer('bksyncgreen_version')) {
            $nombre = sanitize_text_field($_POST['nombre_version']);
            $descripcion = sanitize_textarea_field($_POST['descripcion_version']);
            
            if (!empty($nombre)) {
                $resultado = bksyncgreen_crear_version($nombre, $descripcion);
                if ($resultado) {
                    echo '<div class="notice notice-success is-dismissible"><p>Versión "' . esc_html($nombre) . '" creada correctamente.</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Error al crear la versión.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>El nombre de la versión no puede estar vacío.</p></div>';
            }
        }
    }
    
    $tabla_versiones = $wpdb->prefix . 'bksyncgreen_versions';
    $tabla_commits = $wpdb->prefix . 'bksyncgreen_commits';
    $tabla_cambios = $wpdb->prefix . 'bksyncgreen_changes';
    
    // Obtener versiones con estadísticas
    $versiones = $wpdb->get_results("
        SELECT v.*, 
               COUNT(DISTINCT c.id) as total_commits,
               COUNT(ch.id) as total_cambios
        FROM $tabla_versiones v
        LEFT JOIN $tabla_commits c ON v.id = c.id_version
        LEFT JOIN $tabla_cambios ch ON c.id = ch.id_commit
        GROUP BY v.id
        ORDER BY v.fecha_creacion DESC
    ");
    
    echo '<div class="wrap">';
    echo '<h1>Versiones del Sistema</h1>';
    echo '<p>Las versiones agrupan commits, y los commits agrupan cambios individuales en la base de datos.</p>';
    
    // Formulario para crear nueva versión
    echo '<div class="card" style="max-width: 600px; margin-bottom: 20px;">';
    echo '<h2>Crear Nueva Versión</h2>';
    echo '<form method="post">';
    wp_nonce_field('bksyncgreen_version');
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="nombre_version">Nombre de la versión</label></th>';
    echo '<td><input type="text" id="nombre_version" name="nombre_version" class="regular-text" placeholder="Ej: v1.0.0, Desarrollo, Testing" required /></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row"><label for="descripcion_version">Descripción</label></th>';
    echo '<td><textarea id="descripcion_version" name="descripcion_version" rows="3" cols="50" class="regular-text" placeholder="Describe el propósito de esta versión..."></textarea></td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit"><input type="submit" name="bksyncgreen_crear_version" class="button-primary" value="Crear Versión" /></p>';
    echo '</form>';
    echo '</div>';
    
    // Tabla de versiones
    if (!empty($versiones)) {
        echo '<div class="card">';
        echo '<h2>Versiones Registradas</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>ID</th>';
        echo '<th>Nombre</th>';
        echo '<th>Descripción</th>';
        echo '<th>Estado</th>';
        echo '<th>Commits</th>';
        echo '<th>Cambios</th>';
        echo '<th>Usuario</th>';
        echo '<th>Fecha Creación</th>';
        echo '<th>Fecha Versión</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($versiones as $version) {
            $usuario = get_userdata($version->usuario_id);
            $nombre_usuario = $usuario ? esc_html($usuario->user_login) : 'Usuario eliminado';
            
            $estado_clase = $version->estado === 'open' ? 'text-warning' : 'text-success';
            $estado_texto = $version->estado === 'open' ? 'Abierta' : 'Cerrada';
            
            echo '<tr>';
            echo '<td>' . esc_html($version->id) . '</td>';
            echo '<td><strong>' . esc_html($version->nombre) . '</strong></td>';
            echo '<td>' . esc_html($version->descripcion) . '</td>';
            echo '<td><span class="' . $estado_clase . '">' . esc_html($estado_texto) . '</span></td>';
            echo '<td>' . esc_html($version->total_commits) . '</td>';
            echo '<td>' . esc_html($version->total_cambios) . '</td>';
            echo '<td>' . esc_html($nombre_usuario) . '</td>';
            echo '<td>' . esc_html($version->fecha_creacion) . '</td>';
            echo '<td>' . esc_html($version->fecha_version ?: '-') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-info"><p>No hay versiones registradas.</p></div>';
    }
    
    echo '</div>';
}

// Página de commits
function bksyncgreen_commits_page() {
    global $wpdb;
    
    // Procesar creación de commit
    if (isset($_POST['bksyncgreen_crear_commit']) && isset($_POST['descripcion_commit'])) {
        if (check_admin_referer('bksyncgreen_commit')) {
            $descripcion = sanitize_textarea_field($_POST['descripcion_commit']);
            if (!empty($descripcion)) {
                $resultado = bksyncgreen_crear_commit($descripcion);
                if ($resultado) {
                    echo '<div class="notice notice-success is-dismissible"><p>Commit creado correctamente.</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Error al crear el commit.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>La descripción del commit no puede estar vacía.</p></div>';
            }
        }
    }
    
    $tabla_commits = $wpdb->prefix . 'bksyncgreen_commits';
    $tabla_versiones = $wpdb->prefix . 'bksyncgreen_versions';
    $tabla_cambios = $wpdb->prefix . 'bksyncgreen_changes';
    
    // Obtener commits con información
    $commits = $wpdb->get_results("
        SELECT c.*, u.user_login, v.nombre as version_nombre,
               COUNT(ch.id) as num_cambios
        FROM $tabla_commits c
        LEFT JOIN {$wpdb->users} u ON c.usuario_id = u.ID
        LEFT JOIN $tabla_versiones v ON c.id_version = v.id
        LEFT JOIN $tabla_cambios ch ON c.id = ch.id_commit
        GROUP BY c.id
        ORDER BY c.fecha_creacion DESC
    ");
    
    echo '<div class="wrap">';
    echo '<h1>Gestión de Commits</h1>';
    echo '<p>Los commits agrupan cambios individuales en la base de datos.</p>';
    
    // Formulario para crear commit
    echo '<div class="card" style="max-width: 600px; margin-bottom: 20px;">';
    echo '<h2>Crear Nuevo Commit</h2>';
    echo '<form method="post">';
    wp_nonce_field('bksyncgreen_commit');
    echo '<p><label for="descripcion_commit"><strong>Descripción del commit:</strong></label></p>';
    echo '<p><textarea id="descripcion_commit" name="descripcion_commit" rows="3" cols="50" style="width: 100%;" placeholder="Describe los cambios realizados..."></textarea></p>';
    echo '<p><input type="submit" name="bksyncgreen_crear_commit" class="button-primary" value="Crear Commit" /></p>';
    echo '</form>';
    echo '</div>';
    
    // Tabla de commits
    if (!empty($commits)) {
        echo '<div class="card">';
        echo '<h2>Commits Registrados</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>ID</th>';
        echo '<th>Versión</th>';
        echo '<th>Descripción</th>';
        echo '<th>Estado</th>';
        echo '<th>Cambios</th>';
        echo '<th>Usuario</th>';
        echo '<th>Fecha Creación</th>';
        echo '<th>Fecha Commit</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($commits as $commit) {
            $estado_class = $commit->estado === 'pending' ? 'text-warning' : 'text-success';
            $estado_text = $commit->estado === 'pending' ? 'Pendiente' : 'Confirmado';
            $usuario_nombre = $commit->user_login ?: 'Usuario eliminado';
            
            echo '<tr>';
            echo '<td>' . esc_html($commit->id) . '</td>';
            echo '<td>' . esc_html($commit->version_nombre ?: 'Sin versión') . '</td>';
            echo '<td>' . esc_html($commit->descripcion) . '</td>';
            echo '<td><span class="' . $estado_class . '">' . $estado_text . '</span></td>';
            echo '<td>' . esc_html($commit->num_cambios) . '</td>';
            echo '<td>' . esc_html($usuario_nombre) . '</td>';
            echo '<td>' . esc_html($commit->fecha_creacion) . '</td>';
            echo '<td>' . esc_html($commit->fecha_commit ?: '-') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-info"><p>No hay commits registrados.</p></div>';
    }
    
    echo '</div>';
} 