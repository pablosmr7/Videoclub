<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>

<body>
    <?php

    $db = new mysqli("localhost", "root", "", "videoclub");

    // Miramos el valor de la variable "action", si existe. Si no, le asignamos una acción por defecto
    if (isset($_REQUEST["action"])) {
        $action = $_REQUEST["action"];
    } else {
        $action = "mostrarListaPeliculas";  // Acción por defecto
    }

    // CONTROL DE FLUJO PRINCIPAL
    // El programa saltará a la sección del switch indicada por la variable "action"


    switch ($action) {
        // --------------------------------- MOSTRAR LISTA DE PELICULAS ----------------------------------------
        
        case "mostrarListaPeliculas":
            echo "<h1>Videoclub</h1>";

            echo "<a href='index.php'>Lista de Peliculas</a>";
            echo "<a href='index.php?action=mostrarListaPersonas'> Lista de Reparto </a>";

            // Buscamos todas las peliculas
            if ($result = $db->query("SELECT * FROM peliculas
                                        INNER JOIN actuan ON peliculas.idPelicula = actuan.idPeli
                                        INNER JOIN personas ON actuan.idActor = personas.idPersona
                                        ORDER BY peliculas.titulo")) {

                // La consulta se ha ejecutado con éxito. Vamos a ver si contiene registros
                if ($result->num_rows != 0) {
                    // La consulta ha devuelto registros: vamos a mostrarlos

                    // Primero, el formulario de búsqueda
                    echo "<form action='index.php'>
                                <input type='hidden' name='action' value='buscarPeliculas'>
                                <input type='text' name='textoBusqueda'>
                                <input type='submit' value='Buscar'>
                                </form><br>";

                    // Ahora, la tabla con los datos de las peliculas
                    echo "<table border ='1'>";
                        echo "<th>Titulo</th>";
                        echo "<th>Genero</th>";
                        echo "<th>Pais</th>";
                        echo "<th>Año</th>";
                        echo "<th>Cartel</th>";
                        echo "<th colspan='2'>Director</th>";
                        echo "<th colspan='2'>Opciones</th>";
                        
                    while ($fila = $result->fetch_object()) {
                        echo "<tr>";
                        echo "<td>" . $fila->titulo . "</td>";
                        echo "<td>" . $fila->genero . "</td>";
                        echo "<td>" . $fila->pais . "</td>";
                        echo "<td>" . $fila->ano . "</td>";
                        echo "<td><img src='images/". $fila->cartel ."' alt='foto' width='100' height='150'></td>";
                        echo "<td>" . $fila->nombre . "</td>";
                        echo "<td>" . $fila->apellidos . "</td>";
                        echo "<td><a href='index.php?action=formularioModificarPelicula&idPelicula=" . $fila->idPelicula . "'>Modificar</a></td>";
                        echo "<td><a href='index.php?action=borrarPelicula&idPelicula=" . $fila->idPelicula . "'>Borrar</a></td>";
                        echo "</tr>";
                    }
                    echo "</table>";

                } else {
                    // La consulta no contiene registros
                    echo "No se encontraron datos";
                }

            } else {
                // La consulta ha fallado
                echo "Error al tratar de recuperar los datos de la base de datos. Por favor, inténtelo más tarde";
            }

            echo "<p><a href='index.php?action=formularioInsertarPeliculas'>Nuevo</a></p>";
            break;
            
            // --------------------------------- FORMULARIO ALTA DE PELICULAS ----------------------------------------

        case "formularioInsertarPeliculas":
            echo "<h1>Insercion de peliculas</h1>";

            // Creamos el formulario con los de la pelicula
            echo "<form enctype='multipart/form-data' action = 'index.php' method = 'post'>
                    Título:<input type='text' name='titulo'><br>
                    Género:<input type='text' name='genero'><br>
                    País:<input type='text' name='pais'><br>
                    Año:<input type='text' name='ano'><br>
                    Cartel:<input type='file' name='cartel'><br>
                    <br>";

            // Añadimos un selector para el id de las personas
            $result = $db->query("SELECT * FROM personas");
            echo "Directores: <select name='autor[]' multiple='true'>";
            while ($fila = $result->fetch_object()) {
                echo "<option value='" . $fila->idPersona . "'>" . $fila->nombre . " " . $fila->apellidos . "</option>";
            }
            echo "</select>";
            echo "<a href='index.php?action=formularioInsertarPeresonas'>Añadir nuevo</a><br>";

            // Finalizamos el formulario
            echo "  <input type='hidden' name='action' value='insertarPelicula'>
					<input type='submit'>
				</form>";
            echo "<p><a href='index.php'>Volver</a></p>";

            break;


            
            // --------------------------------- INSERTAR PELICULAS ----------------------------------------

        case "insertarPelicula":
            echo "<h1>Alta de peliculas</h1>";

            $dir_subida = 'C:/xampp/htdocs/peliculas/images/';
            
            // Vamos a procesar el formulario de alta de las peliculas
            // Primero, recuperamos todos los datos del formulario
            $titulo = $_REQUEST["titulo"];
            $genero = $_REQUEST["genero"];
            $pais = $_REQUEST["pais"];
            $ano = $_REQUEST["ano"];
            $fichero_subido = $dir_subida . basename($_FILES['cartel']['name']);
            move_uploaded_file($_FILES['cartel']['tmp_name'], $fichero_subido);
            
            $autores = $_REQUEST["autor"];

            // Lanzamos el INSERT contra la BD.
           
            $db->query("INSERT INTO peliculas (titulo,genero,pais,ano,cartel) VALUES ('$titulo','$genero', '$pais', '$ano', '".basename($_FILES['cartel']['name'])."')");
            if ($db->affected_rows == 1) {
                // Si la inserción de la pelicula ha funcionado, seguimos insertando personas
                // Tenemos que averiguar qué idPelicula se ha asignado a la pelicula que acabamos de insertar
                $result = $db->query("SELECT MAX(idPelicula) AS ultimoIdPelicula FROM peliculas");
                $idPelicula = $result->fetch_object()->ultimoIdPelicula;
                // Ya podemos insertar todos las personas junto a la pelicula en la que participan en la tabla "actuan"
                foreach ($autores as $idAutor) {
                    $db->query("INSERT INTO actuan(idPeli, idActor) VALUES('$idPelicula', '$idAutor')");
                }
                echo "Pelicula insertada con éxito";
            } else {
                // Si la inserción del libro ha fallado, mostramos mensaje de error
                echo "Ha ocurrido un error al insertar la pelicula. Por favor, inténtelo más tarde.";
            }
            echo "<p><a href='index.php'>Volver</a></p>";

            break;

            

            // --------------------------------- BORRAR PELICULAS ----------------------------------------

        case "borrarPelicula":
            echo "<h1>Borrar Peliculas</h1>";

            // Recuperamos el id de la pelicula y lanzamos el DELETE contra la BD
            $idPelicula = $_REQUEST["idPelicula"];
            $db->query("DELETE FROM peliculas WHERE idPelicula = '$idPelicula'");

            // Mostramos mensaje con el resultado de la operación
            if ($db->affected_rows == 0) {
                echo "Ha ocurrido un error al borrar la pelicula. Por favor, inténtelo de nuevo";
            } else {
                echo "Pelicula borrada con éxito";
            }
            echo "<p><a href='index.php'>Volver</a></p>";

            break;


            // --------------------------------- BUSCAR PELICULAS ----------------------------------------

        case "buscarPeliculas":
            // Recuperamos el texto de búsqueda de la variable de formulario
            $textoBusqueda = $_REQUEST["textoBusqueda"];
            echo "<h1>Resultados de la búsqueda: \"$textoBusqueda\"</h1>";

            // Buscamos las peliculas del videoclub que coincidan con el texto de búsqueda
            if ($result = $db->query("SELECT * FROM peliculas
					INNER JOIN actuan ON peliculas.idPelicula = actuan.idPeli
					INNER JOIN personas ON actuan.idActor = personas.idPersona
					WHERE peliculas.titulo LIKE '%$textoBusqueda%'
					OR peliculas.genero LIKE '%$textoBusqueda%'
					OR personas.nombre LIKE '%$textoBusqueda%'
					OR personas.apellidos LIKE '%$textoBusqueda%'
					ORDER BY peliculas.titulo")) {

                // La consulta se ha ejecutado con éxito. Vamos a ver si contiene registros
                if ($result->num_rows != 0) {
                    // La consulta ha devuelto registros: vamos a mostrarlos
                    // Primero, el formulario de búsqueda
                    echo "<form action='index.php'>
								<input type='hidden' name='action' value='buscarPeliculas'>
                            	<input type='text' name='textoBusqueda'>
								<input type='submit' value='Buscar'>
                          </form><br>";
                    // Después, la tabla con los datos
                    echo "<table border ='1'>";
                    while ($fila = $result->fetch_object()) {
                        echo "<tr>";
                        echo "<td>" . $fila->titulo . "</td>";
                        echo "<td>" . $fila->genero . "</td>";
                        echo "<td>" . $fila->pais . "</td>";
                        echo "<td>" . $fila->ano . "</td>";
                        echo "<td><img src='images/". $fila->cartel ."' alt='foto' width='100' height='150'></td>";
                        echo "<td>" . $fila->nombre . "</td>";
                        echo "<td>" . $fila->apellidos . "</td>";
                        echo "<td><a href='index.php?action=formularioModificarPelicula&idPelicula=" . $fila->idPelicula . "'>Modificar</a></td>";
                        echo "<td><a href='index.php?action=borrarPelicula&idPelicula=" . $fila->idPelicula . "'>Borrar</a></td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    // La consulta no contiene registros
                    echo "No se encontraron datos";
                }
            } else {
                // La consulta ha fallado
                echo "Error al tratar de recuperar los datos de la base de datos. Por favor, inténtelo más tarde";
            }

            echo "<p><a href='index.php?action=formularioInsertarPeliculas'>Nuevo</a></p>";
            echo "<p><a href='index.php'>Volver</a></p>";
            break;


            
            // --------------------------------- FORMULARIO MODIFICAR PELICULAS ----------------------------------------
                //Sans modificar imagenes

        case "formularioModificarPelicula":
            echo "<h1>Modificación de peliculas</h1>";

            // Recuperamos el id de la pelicula que vamos a modificar y sacamos el resto de sus datos de la BD
            $idPelicula = $_REQUEST["idPelicula"];
            $result = $db->query("SELECT * FROM peliculas WHERE peliculas.idPelicula = '$idPelicula'");
            $pelicula = $result->fetch_object();

            // Creamos el formulario con los campos de la pelicula
            // y lo rellenamos con los datos que hemos recuperado de la BD
            echo "<form action = 'index.php' method = 'get'>
				    <input type='hidden' name='idPelicula' value='$idPelicula'>
                    Título:<input type='text' name='titulo' value='$pelicula->titulo'><br>
                    Género:<input type='text' name='genero' value='$pelicula->genero'><br>
                    País:<input type='text' name='pais' value='$pelicula->pais'><br>
                    Año:<input type='text' name='ano' value='$pelicula->ano'><br>
                    Cartel:<input type='text' name='numPaginas' value='$pelicula->cartel'><br>";

            // Vamos a añadir un selector para el id del reparto.
            // Para que salgan preseleccionados los actores que estamos modificando, vamos a buscar
            // también a esas personas
            $todosLosAutores = $db->query("SELECT * FROM personas");  // Obtener todo el reparto
            $autoresPelicula = $db->query("SELECT idActor FROM actuan WHERE idPeli = '$idPelicula'");             // Obtener solo el reparto de la pelicula actual
            // Vamos a convertir esa lista de actores en un array de ids de personas
            $listaAutoresPelicula = array();
            while ($autor = $autoresPelicula->fetch_object()) {
                $listaAutoresPelicula[] = $autor->idActor;
            }

            // Ya tenemos todos los datos para añadir el selector de autores al formulario
            echo "Directores: <select name='autor[]' multiple size='3'>";
            while ($fila = $todosLosAutores->fetch_object()) {
                if (in_array($fila->idPersona, $listaAutoresPelicula))
                    echo "<option value='$fila->idPersona' selected>$fila->nombre $fila->apellidos</option>";
                else
                    echo "<option value='$fila->idPersona'>$fila->nombre $fila->apellidos</option>";
            }
            echo "</select>";

            // Por último, un enlace para crear un nuevo actor
            echo "<a href='index.php?action=formularioInsertarPeresonas'>Añadir nuevo</a><br>";

            // Finalizamos el formulario
            echo "  <input type='hidden' name='action' value='modificarPelicula'>
                    <input type='submit'>
                  </form>";
            echo "<p><a href='index.php'>Volver</a></p>";

            break;


            
            // --------------------------------- MODIFICAR PELICULAS ----------------------------------------

        case "modificarPelicula":
            echo "<h1>Modificación de Peliculas</h1>";

            // Vamos a procesar el formulario de modificación de las peliculas
            // Primero, recuperamos todos los datos del formulario
            $idPelicula = $_REQUEST["idPelicula"];
            $titulo = $_REQUEST["titulo"];
            $genero = $_REQUEST["genero"];
            $pais = $_REQUEST["pais"];
            $ano = $_REQUEST["ano"];
            $autores = $_REQUEST["autor"];


            echo "UPDATE peliculas SET
            titulo = '$titulo',
            genero = '$genero',
            pais = '$pais',
            ano = '$ano'
            WHERE idPelicula = '$idPelicula'";



            // Lanzamos el UPDATE contra la base de datos.
            $db->query("UPDATE peliculas SET
							titulo = '$titulo',
							genero = '$genero',
							pais = '$pais',
							ano = '$ano'
							WHERE idPelicula = '$idPelicula'");

           
                // Si la modificación de la pelicula ha funcionado, continuamos actualizando la tabla "escriben".

              
                $db->query("DELETE FROM actuan WHERE idPeli = '$idPelicula'");

                // Ya podemos insertar todos los actores junto con el libro en "actuan"
                foreach ($autores as $idActor) {


                    $db->query("INSERT INTO actuan(idPeli, idActor) VALUES('$idPelicula', '$idActor)' )");
                }
                echo "Pelicula actualizada con éxito";
           
            echo "</br><p><a href='index.php'>Volver</a></p>";
            break;





//---------------------------- AQUI COMIENZA EL CRUD PERSONAS ¡ACHTUNG! HECHO DEPRISA Y CORRIENDO----------------------------------


                // --------------------------------- MOSTRAR PERSONAS ----------------------------------------
            case "mostrarListaPersonas":
                echo "<h1>Videoclub</h1>";
    
                echo "<a href='index.php'>Lista de Peliculas</a>";
                echo "<a href='index.php?action=mostrarListaPersonas'> Lista de Reparto </a>";
    
                // Este Codigo va a ser igual que el de Index, pero presentaremos los datos de forma distinta
                if ($result = $db->query("SELECT * FROM personas
                                            INNER JOIN actuan ON personas.idPersona = actuan.idActor
                                            INNER JOIN peliculas ON actuan.idPeli = peliculas.idPelicula
                                            ORDER BY personas.nombre")) {
    
                    // La consulta se ha ejecutado con éxito. Vamos a ver si contiene registros
                    if ($result->num_rows != 0) {
                        // La consulta ha devuelto registros: vamos a mostrarlos
    
                        // El formulario de busqueda va a ser el mismo que el de PELICULAS, ya que la informacion se solapa casi entera
                        echo "<form action='index.php'>
                                    <input type='hidden' name='action' value='buscarPeliculas'>
                                    <input type='text' name='textoBusqueda'>
                                    <input type='submit' value='Buscar'>
                                    </form><br>";
    
                        // Ahora, la tabla con los datos de las peliculas
                        echo "<table border ='1'>";
                            echo "<th colspan='2'>Reparto</th>";
                            echo "<th>Actor</th>";
                            echo "<th>Titulo</th>";
                            echo "<th>Genero</th>";
                            echo "<th>Cartel</th>";
                            echo "<th colspan='2'>Opciones de Reparto</th>";
                            
                        while ($fila = $result->fetch_object()) {
                            echo "<tr>";
                            echo "<td>" . $fila->nombre . "</td>";
                            echo "<td>" . $fila->apellidos . "</td>";
                            echo "<td><img src='images/". $fila->fotografia ."' alt='foto' width='100' height='150'></td>";
                            echo "<td>" . $fila->titulo . "</td>";
                            echo "<td>" . $fila->genero . "</td>";
                            echo "<td><img src='images/". $fila->cartel ."' alt='foto' width='100' height='150'></td>";
                            echo "<td><a href='index.php?action=formularioModificarPersona&idPersona=" . $fila->idPersona . "'>Modificar</a></td>";
                            echo "<td><a href='index.php?action=borrarActor&idPersona=" . $fila->idPersona . "'>Borrar</a></td>";
                            echo "</tr>";
                        }
                        echo "</table>";
    
                    } else {
                        // La consulta no contiene registros
                        echo "No se encontraron datos";
                    }
    
                } else {
                    // La consulta ha fallado
                    echo "Error al tratar de recuperar los datos de la base de datos. Por favor, inténtelo más tarde";
                }
    
                echo "<p><a href='index.php?action=formularioInsertarPeresonas'>Nuevo Actor</a></p>";
                break;
                


            // --------------------------------- FORMULARIO ALTA DE PERSONAS ----------------------------------------

        case "formularioInsertarPeresonas":
            echo "<h1>Insercion de Reparto</h1>";

            // Creamos el formulario con los de la pelicula
            echo "<form enctype='multipart/form-data' action = 'index.php' method = 'post'>
                    Nombre:<input type='text' name='nombre'><br>
                    Apellidos:<input type='text' name='apellidos'><br>
                    Fotografia:<input type='file' name='fotografia'><br>
                    <br>";


            // Finalizamos el formulario
            echo "  <input type='hidden' name='action' value='insertarPersona'>
					<input type='submit'>
				</form>";
            echo "<p><a href='index.php'>Volver</a></p>";

            break;


            // --------------------------------- INSERTAR PELICULAS ----------------------------------------

        case "insertarPersona":
            echo "<h1>Alta de peliculas</h1>";

            $dir_subida = 'C:/xampp/htdocs/peliculas/images/';
            
            // Vamos a procesar el formulario de alta de las peliculas
            // Primero, recuperamos todos los datos del formulario
            $nombre = $_REQUEST["nombre"];
            $apellidos = $_REQUEST["apellidos"];
            $fichero_subido = $dir_subida . basename($_FILES['fotografia']['name']);
            move_uploaded_file($_FILES['fotografia']['tmp_name'], $fichero_subido);
            

            // Lanzamos el INSERT contra la BD.
           
            $db->query("INSERT INTO personas (nombre,apellidos,fotografia) VALUES ('$nombre','$apellidos', '".basename($_FILES['fotografia']['name'])."')");
            if ($db->affected_rows == 1) {
                // Si la inserción de la pelicula ha funcionado, seguimos insertando personas
                // Tenemos que averiguar qué idPelicula se ha asignado a la pelicula que acabamos de insertar
                $result = $db->query("SELECT MAX(idPersona) AS ultimoIdPersona FROM personas");
                $idPersona = $result->fetch_object()->ultimoIdPersona;
                // Ya podemos insertar todos las personas junto a la pelicula en la que participan en la tabla "actuan"

                echo "Persona insertada con éxito";
            } else {
                // Si la inserción del actor ha fallado, mostramos mensaje de error
                echo "Ha ocurrido un error al insertar la persona. Por favor, inténtelo más tarde.";
            }
            echo "<p><a href='index.php?action=mostrarListaPersonas'>Volver a reparto</a></p>";

            break;


             // --------------------------------- BORRAR PERSONAS ----------------------------------------

        case "borrarActor":
            echo "<h1>Borrar Actor</h1>";

            // Recuperamos el id de la pelicula y lanzamos el DELETE contra la BD
            $idPersona = $_REQUEST["idPersona"];
            $db->query("DELETE FROM personas WHERE idPersona = '$idPersona'");

            // Mostramos mensaje con el resultado de la operación
            if ($db->affected_rows == 0) {
                echo "Ha ocurrido un error al borrar el actor. Por favor, inténtelo de nuevo";
            } else {
                echo "Actor borrado con éxito";
            }
            echo "<p><a href='index.php'>Volver</a></p>";

            break;



            



            // --------------------------------- ACTION NO ENCONTRADA ----------------------------------------

        default:
            echo "<h1>Error 404: página no encontrada</h1>";
            echo "<a href='index.php'>Volver</a>";
            break;
        
    } 

    ?>

</body>

</html>