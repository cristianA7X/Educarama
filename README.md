# Educarama ⚜️

> **Educación + Rama**: Una herramienta digital para el seguimiento del crecimiento y la progresión personal en el Movimiento Scout.

![Logo de Educarama](Logo1)

## 📖 Sobre el Proyecto
Educarama nace como un proyecto universitario para la carrera de **Desarrollo de Software**. [cite_start]Su objetivo es solucionar la fragmentación de la información en los grupos scouts, donde actualmente se depende de métodos manuales o planillas de Excel genéricas que dificultan el seguimiento pedagógico[cite: 10, 11, 12].

[cite_start]A diferencia de los sistemas oficiales centrados en lo administrativo, Educarama es una herramienta diseñada para el **trabajo de campo del educador**, enfocándose en la visualización clara y rápida del progreso de cada joven[cite: 61, 78, 80].

---

## 🚀 Funcionalidades Core (MVP 1)
[cite_start]En esta primera etapa, el sistema se centra en los requerimientos operativos clave para garantizar una gestión de punta a punta[cite: 31, 49]:

* [cite_start]**Gestión de Educadores:** Registro de perfiles con asignación de ramas y permisos según el rol[cite: 33].
* [cite_start]**Gestión de Scouts:** Alta de beneficiarios con datos personales (DNI, edad) y gestión de archivos/documentación adjunta[cite: 34].
* [cite_start]**Progresión Básica:** Módulo para la asignación directa de competencias y automatización de la entrega de insignias[cite: 35, 50].
* [cite_start]**Gestión de Proyectos Scout:** Panel de control para identificar la etapa actual del ciclo de proyecto de cada grupo[cite: 36, 52].

---

## 🛠️ Stack Tecnológico
* **Backend:** PHP (API y lógica de servidor).
* **Base de Datos:** MySQL.
* **Frontend:** HTML5, CSS3, JavaScript.
* **Framework CSS:** Bootstrap 5.
* **Entorno de Desarrollo:** XAMPP (Localhost).

### Estructura de Directorios
```text
/api        # Endpoints y lógica de negocio
/config     # Configuración de base de datos y constantes
/includes   # Componentes reutilizables (header, footer, funciones)
/pages      # Vistas principales de la aplicación
/public     # Recursos estáticos (imágenes, CSS, JS)
database.sql # Script para la creación de la base de datos
