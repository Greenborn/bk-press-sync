# Arquitectura básica de un plugin para WordPress

Este documento describe la estructura y los componentes esenciales de BkSyncGreen, un plugin moderno para la última versión de WordPress cuyo objetivo es el versionado de los cambios realizados en todas las tablas existentes en la instalación de WordPress.

## 1. Estructura de archivos y carpetas

Un plugin típico de WordPress debe tener la siguiente estructura mínima:

```
bksyncgreen/
├── bksyncgreen.php           # Archivo principal del plugin
├── readme.txt               # Descripción y documentación básica
├── assets/                  # Imágenes, íconos, scripts, etc.
├── includes/                # Archivos PHP auxiliares (clases, funciones)
│   └── bksyncgreen-ejemplo.php
├── languages/               # Archivos de traducción (.pot, .po, .mo)
├── admin/                   # Archivos específicos para el panel de administración
│   └── bksyncgreen-admin.php
├── public/                  # Archivos para la parte pública (frontend)
└── uninstall.php            # Script para desinstalación limpia
```

## 2. Archivo principal del plugin

- Debe tener el encabezado estándar de WordPress con nombre, descripción, versión, autor, etc.
- Es el punto de entrada donde se cargan el resto de los componentes.

## 3. Carga de dependencias

- Utilizar `require_once` o un autoloader para cargar archivos de la carpeta `includes/`.
- Separar la lógica de administración (`admin/`) y la lógica pública (`public/`).

## 4. Ganchos (Hooks)

- Utilizar acciones (`add_action`) y filtros (`add_filter`) para integrarse con el ciclo de vida de WordPress.
- Registrar funciones para inicialización, activación y desactivación del plugin (`register_activation_hook`, `register_deactivation_hook`).

## 5. Internacionalización (i18n)

- Preparar el plugin para traducción usando funciones como `__()` y `_e()`.
- Incluir archivos de idioma en la carpeta `languages/`.

## 6. Seguridad

- Validar y sanitizar todas las entradas de usuario.
- Utilizar `nonce` para formularios y acciones sensibles.
- Respetar las capacidades de usuario (`current_user_can`).

## 7. Buenas prácticas

- Seguir el estándar de codificación de WordPress.
- Documentar el código y las funciones principales.
- Evitar conflictos de nombres usando prefijos únicos.

## 8. Ejemplo de encabezado de plugin

```php
<?php
/*
Plugin Name: Mi Plugin Ejemplo
Description: Un ejemplo de plugin para WordPress.
Version: 1.0.0
Author: Tu Nombre
*/
```

## 9. Estrategia para el versionado de cambios en tablas de WordPress

El objetivo principal de BkSyncGreen es registrar y versionar todos los cambios (inserciones, actualizaciones y eliminaciones) realizados en las tablas de la base de datos de WordPress. A continuación se describen las estrategias y consideraciones técnicas para lograrlo:

### a) Detección de cambios
- Utilizar los hooks de WordPress (`save_post`, `deleted_post`, `updated_post_meta`, etc.) para detectar cambios en las tablas estándar (posts, users, options, etc.).
- Para tablas personalizadas o cambios directos en la base de datos, considerar el uso de triggers a nivel de base de datos (requiere acceso y permisos en MySQL) o el monitoreo de queries mediante filtros como `query` o `dbdelta`.

### b) Registro de versiones
- Crear una tabla propia del plugin (por ejemplo, `wp_bksyncgreen_versions`) donde se almacenen los cambios detectados.
- Guardar información relevante: tabla afectada, tipo de operación (insert/update/delete), datos anteriores y nuevos, usuario responsable, fecha y hora.

### c) Restauración y auditoría
- Implementar funciones para consultar el historial de cambios por tabla, registro o usuario.
- Permitir la restauración de versiones anteriores de un registro si es necesario.

### d) Consideraciones de rendimiento y seguridad
- Optimizar el almacenamiento para evitar crecimiento excesivo de la tabla de versiones.
- Asegurar que solo usuarios autorizados puedan acceder o restaurar versiones.
- Cumplir con la normativa de protección de datos (GDPR, LOPD, etc.) si se almacenan datos sensibles.

### e) Ejemplo de estructura de tabla de versiones

```sql
CREATE TABLE wp_bksyncgreen_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(255) NOT NULL,
    operacion ENUM('insert','update','delete') NOT NULL,
    datos_anteriores LONGTEXT,
    datos_nuevos LONGTEXT,
    usuario_id BIGINT UNSIGNED,
    fecha DATETIME NOT NULL
);
```

---

Esta estrategia puede adaptarse y ampliarse según las necesidades del proyecto y la complejidad de la instalación de WordPress. 