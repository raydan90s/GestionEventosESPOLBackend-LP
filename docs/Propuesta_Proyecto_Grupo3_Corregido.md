# ESCUELA SUPERIOR POLITÉCNICA DEL LITORAL
## FACULTAD DE INGENIERÍA EN ELECTRICIDAD Y COMPUTACIÓN

**Propuesta Proyecto Parcial**
**Plataforma Web para la Organización y Gestión de Eventos ESPOL**

**Presentado por:** Equipo 3
* Juliana Burgos
* Eimmy Ochoa
* Diego Parrales

**3 de agosto de 2026**

---

## 1. Problemática a resolver

En el campus de la Escuela Superior Politécnica del Litoral (ESPOL), la difusión de actividades extracurriculares, seminarios y talleres se realiza actualmente de manera descentralizada, dependiendo de múltiples canales fragmentados como distintas redes sociales, correos electrónicos masivos y carteleras físicas. Esta alta dispersión de la información dificulta que la comunidad estudiantil acceda de forma consolidada y oportuna a la oferta académica, provocando ruido comunicacional y desinformación.

Como consecuencia directa de esta carencia de un directorio oficial unificado, se evidencia una constante pérdida de oportunidades de participación estudiantil y una baja asistencia a eventos de gran valor para su desarrollo profesional. Abordar esta problemática es vital para maximizar el alcance de las iniciativas de los clubes y facultades, optimizar el uso de los espacios físicos institucionales y garantizar que los recursos organizativos tengan el impacto esperado.

## 2. Objetivos Propuestos

**Objetivo General**
Desarrollar una plataforma web centralizada bajo el patrón MVC que optimice la gestión, publicación y el registro de inscripciones a eventos en la ESPOL, fomentando la participación estudiantil.

**Objetivos Específicos**
* Utilizar un esquema de categorización preestablecida que permita clasificar y estructurar lógicamente la oferta de actividades académicas y extracurriculares.
* Facilitar la creación y el despliegue interactivo de un catálogo de eventos filtrable, controlando variables clave como fechas, ubicación y capacidad máxima.
* Desarrollar un sistema de control de inscripciones en tiempo real que gestione la disponibilidad exacta de cupos y provea un listado de auditoría de asistentes.
* Incorporar un espacio de interacción mediante comentarios en cada evento para dinamizar la participación.

## 3. Alcance del Proyecto

El presente proyecto se centrará en el desarrollo de un prototipo funcional de una aplicación web orientada a la comunidad de la ESPOL. Dentro del alcance operativo y técnico, el sistema abarcará:

* La visualización de un catálogo dinámico de eventos disponibles.
* La utilización de **categorías preestablecidas** para estructurar adecuadamente la oferta de actividades (ej. talleres académicos, clubes extracurriculares, seminarios).
* La administración de eventos con asignación de detalles clave: título, ubicación dentro del campus, fecha y aforo (cupos máximos).
* Un módulo de inscripción de estudiantes que valide y actualice en tiempo real la disponibilidad de cupos.
* Una vista de registro de asistentes para el control logístico por parte de los organizadores.
* **Un módulo de comentarios** que permita a los usuarios leer y escribir observaciones o consultas en la página de cada evento.

**Fuera del alcance:** Debido a las restricciones de tiempo y al enfoque académico de la asignatura, el sistema no incluirá:
* Integración con pasarelas de pago, facturación electrónica o gestión financiera para eventos con costo.
* Plataformas de transmisión en vivo (streaming) o alojamiento de video dentro de la aplicación.
* Envío automatizado de correos electrónicos institucionales o notificaciones push/SMS.
* Desarrollo de una aplicación móvil nativa (el proyecto será estrictamente web, aunque con diseño responsivo).
* Sistema complejo de login con múltiples roles.

## 4. Características de la Solución

Para solventar la problemática de desinformación y dispersión actual, la plataforma integrará las siguientes características clave, distribuidas en el trabajo del equipo:

1. **Directorio Centralizado y Categorizado:** Un entorno digital unificado donde la oferta extracurricular de la ESPOL estará agrupada de forma lógica bajo un sistema de categorías preestablecidas.
2. **Catálogo Interactivo y Filtrable:** Una interfaz visual ágil que mostrará los eventos activos como tarjetas informativas. Los estudiantes podrán descubrir actividades y filtrar los resultados por fecha y categoría.
3. **Gestión Automatizada de Aforo:** Un mecanismo de control de inscripciones que respetará de forma estricta los límites de capacidad física de los espacios en el campus.
4. **Auditoría y Monitoreo de Asistentes:** Un panel de lectura rápido que permitirá a los responsables de un evento visualizar la lista exacta de personas inscritas.
5. **Interacción y Comentarios:** Un espacio bidireccional donde los participantes pueden escribir sus expectativas, opiniones o preguntas, y leer las interacciones de otros miembros de la comunidad, añadiendo valor a la difusión del evento.

## 5. Requerimientos Funcionales Asignados

| Requerimiento | Descripción | Responsable |
| :--- | :--- | :--- |
| **Escribir comentario de evento (Escritura)** | Permite a los usuarios redactar y publicar comentarios en un evento específico, validando el contenido ingresado para fomentar la interacción. | Eimmy Ochoa |
| **Ver comentarios de evento (Lectura)** | Permite consultar y listar todos los comentarios realizados por la comunidad en la vista de detalle de un evento. | Eimmy Ochoa |
| **Crear evento (Escritura)** | Permite publicar un nuevo evento asociándolo a una categoría existente. Requiere validar el ingreso de título, fecha, lugar y definir la capacidad máxima de asistentes. | Juliana Burgos |
| **Ver catálogo de eventos (Lectura)** | Permite visualizar la lista de eventos disponibles mediante tarjetas, implementando consultas que permitan filtrar los resultados. | Juliana Burgos |
| **Registrar inscripción (Escritura)** | Permite a un estudiante separar su lugar en una actividad. La lógica debe validar la disponibilidad de cupos en tiempo real y descontar una unidad del aforo del evento. | Diego Parrales |
| **Ver asistentes (Lectura)** | Permite a los organizadores consultar y generar un listado detallado de las personas que se han inscrito exitosamente a un evento en específico para el control logístico. | Diego Parrales |

## 6. Arquitectura de la Aplicación

**Tipo de Aplicación:** Aplicación Web. Se define de esta forma debido a la necesidad de ofrecer un acceso universal e inmediato a toda la comunidad estudiantil.

**Patrón de Arquitectura:** Cliente-Servidor implementando MVC (Modelo-Vista-Controlador). El proyecto adopta una arquitectura desacoplada. El cliente (Frontend) asume la responsabilidad de la Vista. Por otro lado, el servidor (Backend) centraliza el Controlador (gestionando las rutas, validaciones) y el Modelo (interactuando directamente con la base de datos PostgreSQL).

**Herramientas y Frameworks:**
* **Framework:** React (JavaScript) para el Frontend.
* **API:** Se desarrollará una API RESTful nativa en PHP.
* **Servicios:** XAMPP como suite de infraestructura local y PostgreSQL como motor de base de datos relacional.

## 7. Lenguajes de Programación

**Back-end: PHP**
* *Pros:* Alta expresividad (Writability) y estructuras gramaticales familiares (Readability).
* *Contras:* Baja ortogonalidad y tipado dinámico que reduce parcialmente la confiabilidad (Reliability).

**Front-end: JavaScript / TypeScript (React)**
* *Pros:* Excelente soporte para abstracción (Writability) y mejora en confiabilidad gracias al tipado estático estricto de TypeScript (Reliability).
* *Contras:* Incrementa la verbosidad del código, disminuyendo la simplicidad inicial frente a JavaScript puro (Readability).

## 8. Prototipo de Baja Fidelidad
*Herramienta de Diseño Utilizada: Figma.*

*(Nota: Las imágenes de los prototipos fueron omitidas en esta transcripción, pero se conserva el diseño estructurado según las referencias del documento original).*

**Enlace al Prototipo:** https://ide-shop-87027578.figma.site/

---
**Referencias**
1. MDN Web Docs, "MVC (Model-View-Controller)", Mozilla.
2. Meta Platforms, Inc., "React: The library for web and native user interfaces", React Documentation.
3. The PHP Group, "PHP Manual", PHP Documentation.
4. Oracle Corporation, "MySQL Reference Manual", MySQL Documentation.
5. Apache Friends, "XAMPP Apache + MariaDB + PHP + Perl", Apache Friends.
