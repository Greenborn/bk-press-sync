# Arquitectura del Plugin BkSyncGreen

## Descripción General

BkSyncGreen es un plugin de WordPress que implementa un sistema de versionado de base de datos similar a Git, permitiendo rastrear todos los cambios realizados en las tablas de WordPress y agruparlos en commits y versiones.

## Estructura Jerárquica

El sistema implementa una jerarquía de tres niveles:

### 1. Versiones (Nivel Superior)
- **Propósito**: Agrupan commits relacionados en una versión específica del sistema
- **Estado**: `open` (abierta) o `closed` (cerrada)
- **Campos principales**:
  - `id`: Identificador único
  - `nombre`: Nombre de la versión (ej: "v1.0.0", "Desarrollo")
  - `descripcion`: Descripción de la versión
  - `estado`: Estado actual (open/closed)
  - `usuario_id`: Usuario que creó la versión
  - `fecha_creacion`: Fecha de creación
  - `fecha_version`: Fecha de cierre (cuando se cierra)

### 2. Commits (Nivel Medio)
- **Propósito**: Agrupan cambios individuales con una descripción común
- **Estado**: `pending` (pendiente) o `committed` (confirmado)
- **Campos principales**:
  - `id`: Identificador único
  - `id_version`: Referencia a la versión padre
  - `descripcion`: Descripción del commit
  - `estado`: Estado actual (pending/committed)
  - `usuario_id`: Usuario que creó el commit
  - `fecha_creacion`: Fecha de creación
  - `fecha_commit`: Fecha de confirmación

### 3. Cambios (Nivel Inferior)
- **Propósito**: Registros individuales de cambios en la base de datos
- **Tipos**: `insert`, `update`, `delete`
- **Campos principales**:
  - `id`: Identificador único
  - `id_commit`: Referencia al commit padre
  - `tabla`: Tabla afectada
  - `operacion`: Tipo de operación
  - `datos_anteriores`: Datos antes del cambio
  - `datos_nuevos`: Datos después del cambio
  - `usuario_id`: Usuario que realizó el cambio
  - `fecha`: Fecha del cambio

## Estructura de Archivos

```
bk-press-sync/
├── bksyncgreen.php                 # Archivo principal del plugin
├── includes/
│   └── crear_tabla_versionado.php  # Script de creación de tablas
├── admin/
│   └── bksyncgreen-admin.php       # Interfaz de administración
├── languages/
│   ├── bksyncgreen-es_ES.po        # Traducciones en español
│   ├── bksyncgreen-en_US.po        # Traducciones en inglés
│   └── bksyncgreen-fr_FR.po        # Traducciones en francés
└── readme.txt                      # Documentación del plugin
```

## Base de Datos

### Tabla: `wp_bksyncgreen_versions`
```sql
CREATE TABLE wp_bksyncgreen_versions (
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
);
```

### Tabla: `wp_bksyncgreen_commits`
```sql
CREATE TABLE wp_bksyncgreen_commits (
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
    FOREIGN KEY (id_version) REFERENCES wp_bksyncgreen_versions(id) ON DELETE SET NULL
);
```

### Tabla: `wp_bksyncgreen_changes`
```sql
CREATE TABLE wp_bksyncgreen_changes (
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
    FOREIGN KEY (id_commit) REFERENCES wp_bksyncgreen_commits(id) ON DELETE CASCADE
);
```

## Funcionalidades Principales

### 1. Registro Automático de Cambios
- **Hooks implementados**:
  - `save_post`: Cambios en posts/páginas
  - `profile_update`: Cambios en usuarios
  - `updated_option`: Cambios en opciones

### 2. Gestión de Versiones
- Crear nuevas versiones
- Cerrar versiones existentes
- Ver versiones con sus commits asociados
- Revertir versiones completas

### 3. Gestión de Commits
- Crear commits con descripción
- Confirmar commits pendientes
- Ver commits de una versión
- Revertir commits individuales

### 4. Visualización de Cambios
- Ver cambios de un commit
- Comparar diferencias
- Exportar datos en JSON
- Filtros avanzados

### 5. Estadísticas
- Gráficos de actividad
- Commits por usuario
- Cambios por tabla
- Operaciones por tipo

## Flujo de Trabajo

1. **Activación del Plugin**:
   - Se crean las tres tablas en la base de datos
   - Se crea automáticamente una versión inicial abierta
   - Se crea un commit pendiente inicial

2. **Registro de Cambios**:
   - Los cambios se registran automáticamente en el commit pendiente
   - Se evitan duplicados comparando datos

3. **Creación de Commits**:
   - El usuario puede crear commits con descripción
   - Los commits se asocian a la versión abierta actual

4. **Gestión de Versiones**:
   - Se pueden crear nuevas versiones
   - Al crear una nueva versión, se cierra la anterior
   - Las versiones pueden contener múltiples commits

5. **Revertir Cambios**:
   - Se pueden revertir commits individuales
   - Se pueden revertir versiones completas
   - Las reversiones se ejecutan en orden inverso

## Interfaz de Administración

### Páginas Principales:
1. **Versiones**: Muestra todas las versiones con sus commits
2. **Commits**: Gestión de commits y cambios pendientes
3. **Estadísticas**: Dashboard con gráficos y métricas
4. **Configuración**: Ajustes del plugin

### Características de la UI:
- Bootstrap 5 para el diseño
- DataTables para tablas interactivas
- Chart.js para gráficos
- Modales para detalles
- Filtros avanzados
- Exportación de datos

## Seguridad

- Verificación de nonces en todas las operaciones AJAX
- Sanitización de datos de entrada
- Validación de permisos de usuario
- Transacciones de base de datos para operaciones críticas

## Internacionalización

- Soporte para múltiples idiomas
- Archivos .po/.mo para traducciones
- Textos dinámicos en la interfaz
- Idiomas soportados: Español, Inglés, Francés

## Instalación y Configuración

1. Subir el plugin al directorio `/wp-content/plugins/`
2. Activar el plugin desde el panel de administración
3. Las tablas se crean automáticamente
4. Configurar opciones en "BkSyncGreen > Configuración"

## Uso Recomendado

1. **Desarrollo**: Mantener una versión "Desarrollo" abierta
2. **Testing**: Crear versiones para cada fase de pruebas
3. **Producción**: Crear versiones estables para releases
4. **Mantenimiento**: Usar commits descriptivos para cambios

## Limitaciones

- Solo versiona tablas principales de WordPress
- No versiona archivos (solo base de datos)
- Requiere permisos de administrador
- Puede generar archivos grandes con muchos cambios

## Futuras Mejoras

- Versionado de archivos
- Integración con Git
- API REST para integraciones externas
- Backup automático antes de reversiones
- Notificaciones por email
- Integración con sistemas de tickets 