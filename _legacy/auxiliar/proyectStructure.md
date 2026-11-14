# Project Structure

```
Educarama/
├── auxiliar
│   ├── educarama-mvp2.sql
│   ├── proyectStructure.md
│   ├── Sprint-1.3
│   └── sprint-2.1
├── public
│   ├── css
│   │   └── styles.css
│   ├── fonts
│   │   └── ubuntu
│   │       ├── Ubuntu-Bold.woff2
│   │       ├── Ubuntu-BoldItalic.woff2
│   │       ├── Ubuntu-Italic.woff2
│   │       ├── Ubuntu-Light.woff2
│   │       ├── Ubuntu-LightItalic.woff2
│   │       ├── Ubuntu-Medium.woff2
│   │       ├── Ubuntu-MediumItalic.woff2
│   │       ├── Ubuntu-Regular.woff2
│   │       └── UFL.txt
│   └── index.php
├── src
│   ├── Abstracciones
│   │   ├── ActualizarInterface.php
│   │   ├── AgregarInterface.php
│   │   ├── EliminarInterface.php
│   │   ├── MostrarPorIdInterface.php
│   │   └── MostrarTodoInterface.php
│   ├── Controladores
│   │   ├── AuthController.php
│   │   ├── AuthMiddleware.php
│   │   ├── CategoriaRamaController.php
│   │   ├── eventoControlador.php
│   │   ├── FamiliarController.php
│   │   ├── LogoutController.php
│   │   ├── PatrullaController.php
│   │   ├── ProgresionController.php
│   │   ├── ProtagonistaController.php
│   │   ├── RamaController.php
│   │   └── RegistroController.php
│   ├── Datos
│   │   ├── AccionRepository.php
│   │   ├── CategoriaRama.php
│   │   ├── CompetenciaRepository.php
│   │   ├── Database.php
│   │   ├── DocumentoRepository.php
│   │   ├── educarama.sql
│   │   ├── eventoRepositorio.php
│   │   ├── FamiliarRepository.php
│   │   ├── Gruporepository.php
│   │   ├── InsigniaRepository.php
│   │   ├── PatrullaRepository.php
│   │   ├── PersonaRepository.php
│   │   ├── ProgresionRepository.php
│   │   ├── ProtagonistaRepository.php
│   │   ├── RamaRepository.php
│   │   ├── roles-ramas-competencias-acciones.sql
│   │   ├── RolRepository.php
│   │   ├── SenderoRepository.php
│   │   ├── TipoAccionRepository.php
│   │   └── UsuarioRepository.php
│   ├── Helpers
│   │   └── RamaHelper.php
│   ├── Model
│   │   ├── Accion.php
│   │   ├── AsignacionRol.php
│   │   ├── CategoriaRama.php
│   │   ├── Competencia.php
│   │   ├── Cumple.php
│   │   ├── Desarrolla.php
│   │   ├── Documento.php
│   │   ├── EsFamilia.php
│   │   ├── Familiar.php
│   │   ├── Grupo.php
│   │   ├── Insignia.php
│   │   ├── Obtiene.php
│   │   ├── Patrulla.php
│   │   ├── Persona.php
│   │   ├── Protagonista.php
│   │   ├── Rama.php
│   │   ├── Rol.php
│   │   ├── Sendero.php
│   │   ├── TipoAccion.php
│   │   └── Usuario.php
│   └── Servicios
│       ├── CategoriaRamaService.php
│       ├── EventoService.php
│       ├── FamiliarService.php
│       ├── LoginService.php
│       ├── ObtenerPerfilProtagonistaService.php
│       ├── PatrullaService.php
│       ├── ProgresionService.php
│       ├── ProtagonistaService.php
│       ├── RamaService.php
│       └── RegistrarUsuarioService.php
├── views
│   ├── assets
│   │   └── images
│   │       ├── insignias
│   │       │   ├── Progresion 1 Rama Lobatos y Lobeznas_Lobo Pata Tierna.png
│   │       │   ├── Progresion 2 Rama Lobatos y Lobeznas_Lobo Saltador.png
│   │       │   ├── Progresion 3 Rama Lobatos y Lobeznas_Lobo Rastreador.png
│   │       │   └── Progresion 4 Rama Lobatos y Lobeznas_Lobo Cazador.png
│   │       ├── FlorLizInternacional.jpg
│   │       ├── FlorLizInternacional.png
│   │       ├── FlorLizNacional.png
│   │       ├── Logo1.jpg
│   │       ├── Logo1.png
│   │       └── RamaLogoLyL.png
│   └── pages
│       ├── includes
│       │   ├── alertas.php
│       │   ├── barra_inferior.php
│       │   ├── barra_superior.php
│       │   └── menu_lateral.php
│       ├── alta_patrulla.php
│       ├── alta_protagonista.php
│       ├── alta_rama.php
│       ├── eventos.php
│       ├── gestionar_competencias.php
│       ├── gestionar_eventos.php
│       ├── login.php
│       ├── patrullas.php
│       ├── perfil_patrulla.php
│       ├── perfil_protagonista.php
│       ├── perfil_rama.php
│       ├── protagonistas_nomina.php
│       ├── protagonistas.php
│       ├── ramas.php
│       ├── recuperar_contrasenia.php
│       ├── register.php
│       └── unirse_grupo.php
└── README.md
```
