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

        // --------------------------------- MOSTRAR LISTA DE LIBROS ----------------------------------------
        
        case "mostrarListaPeliculas":
            echo "<h1>Videoclub</h1>";

            // Buscamos todos los libros de la biblioteca
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

                    // Ahora, la tabla con los datos de los libros
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
            break;
            
            // --------------------------------- FORMULARIO ALTA DE PELICULAS ----------------------------------------

        case "formularioInsertarPeliculas":
            echo "<h1>Insercion de peliculas</h1>";

            // Creamos el formulario con los campos del libro
            echo "<form enctype='multipart/form-data' action = 'index.php' method = 'post'>
                    Título:<input type='text' name='titulo'><br>
                    Género:<input type='text' name='genero'><br>
                    País:<input type='text' name='pais'><br>
                    Año:<input type='text' name='ano'><br>
                    Cartel:<input type='file' name='cartel'><br>
                    <br>";

            // Añadimos un selector para el id del autor o autores
            $result = $db->query("SELECT * FROM personas");
            echo "Autores: <select name='autor[]' multiple='true'>";
            while ($fila = $result->fetch_object()) {
                echo "<option value='" . $fila->idPersona . "'>" . $fila->nombre . " " . $fila->apellidos . "</option>";
            }
            echo "</select>";
            echo "<a href='index.php?action=formularioInsertarActores'>Añadir nuevo</a><br>";

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
            
            // Vamos a procesar el formulario de alta de libros
            // Primero, recuperamos todos los datos del formulario
            $titulo = $_REQUEST["titulo"];
            $genero = $_REQUEST["genero"];
            $pais = $_REQUEST["pais"];
            $ano = $_REQUEST["ano"];
            $fichero_subido = $dir_subida . basename($_FILES['cartel']['name']);
            move_uploaded_file($_FILES['cartel']['tmp_name'], $fichero_subido);
            echo $fichero_subido;
            $autores = $_REQUEST["autor"];

            // Lanzamos el INSERT contra la BD.
            echo "INSERT INTO peliculas (titulo,genero,pais,ano,cartel) VALUES ('$titulo','$genero', '$pais', '$ano', '".basename($_FILES['cartel']['name'])."')";
            $db->query("INSERT INTO peliculas (titulo,genero,pais,ano,cartel) VALUES ('$titulo','$genero', '$pais', '$ano', '".basename($_FILES['cartel']['name'])."')");
            if ($db->affected_rows == 1) {
                // Si la inserción del libro ha funcionado, continuamos insertando en la tabla "escriben"
                // Tenemos que averiguar qué idLibro se ha asignado al libro que acabamos de insertar
                $result = $db->query("SELECT MAX(idPelicula) AS ultimoIdPelicula FROM peliculas");
                $idPelicula = $result->fetch_object()->ultimoIdPelicula;
                // Ya podemos insertar todos los autores junto con el libro en "escriben"
                foreach ($autores as $idAutor) {
                    $db->query("INSERT INTO actuan(idPeli, idActor) VALUES('$idPelicula', '$idAutor')");
                }
                echo "Pelicula insertada con éxito";
            } else {
                // Si la inserción del libro ha fallado, mostramos mensaje de error
                echo "Ha ocurrido un error al insertar el libro. Por favor, inténtelo más tarde.";
            }
            echo "<p><a href='index.php'>Volver</a></p>";

            break;

            

            // --------------------------------- BORRAR LIBROS ----------------------------------------

        case "borrarPelicula":
            echo "<h1>Borrar Peliculas</h1>";

            // Recuperamos el id del libro y lanzamos el DELETE contra la BD
            $idPelicula = $_REQUEST["idPelicula"];
            $db->query("DELETE FROM peliculas WHERE idPelicula = '$idPelicula'");

            // Mostramos mensaje con el resultado de la operación
            if ($db->affected_rows == 0) {
                echo "Ha ocurrido un error al borrar la pelicula. Por favor, inténtelo de nuevo";
            } else {
                echo "pelicula borrada con éxito";
            }
            echo "<p><a href='index.php'>Volver</a></p>";

            break;


            // --------------------------------- BUSCAR PELICULAS ----------------------------------------

        case "buscarPeliculas":
            // Recuperamos el texto de búsqueda de la variable de formulario
            $textoBusqueda = $_REQUEST["textoBusqueda"];
            echo "<h1>Resultados de la búsqueda: \"$textoBusqueda\"</h1>";

            // Buscamos los libros de la biblioteca que coincidan con el texto de búsqueda
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
                        echo "<td>" . $fila->cartel . "</td>";
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


            
            // --------------------------------- FORMULARIO MODIFICAR LIBROS ----------------------------------------

        case "formularioModificarPelicula":
            echo "<h1>Modificación de peliculas</h1>";

            // Recuperamos el id del libro que vamos a modificar y sacamos el resto de sus datos de la BD
            $idPelicula = $_REQUEST["idPelicula"];
            $result = $db->query("SELECT * FROM peliculas WHERE peliculas.idPelicula = '$idPelicula'");
            $pelicula = $result->fetch_object();

            // Creamos el formulario con los campos del libro
            // y lo rellenamos con los datos que hemos recuperado de la BD
            echo "<form action = 'index.php' method = 'get'>
				    <input type='hidden' name='idPelicula' value='$idPelicula'>
                    Título:<input type='text' name='titulo' value='$pelicula->titulo'><br>
                    Género:<input type='text' name='genero' value='$pelicula->genero'><br>
                    País:<input type='text' name='pais' value='$pelicula->pais'><br>
                    Año:<input type='text' name='ano' value='$pelicula->ano'><br>
                    Cartel:<input type='text' name='numPaginas' value='$pelicula->cartel'><br>";

            // Vamos a añadir un selector para el id del autor o autores.
            // Para que salgan preseleccionados los autores del libro que estamos modificando, vamos a buscar
            // también a esos autores.
            $todosLosAutores = $db->query("SELECT * FROM personas");  // Obtener todos los autores
            $autoresPelicula = $db->query("SELECT idActor FROM actuan WHERE idPeli = '$idPelicula'");             // Obtener solo los autores del libro que estamos buscando
            // Vamos a convertir esa lista de autores del libro en un array de ids de personas
            $listaAutoresPelicula = array();
            while ($autor = $autoresPelicula->fetch_object()) {
                $listaAutoresPelicula[] = $autor->idActor;
            }

            // Ya tenemos todos los datos para añadir el selector de autores al formulario
            echo "Autores: <select name='autor[]' multiple size='3'>";
            while ($fila = $todosLosAutores->fetch_object()) {
                if (in_array($fila->idPersona, $listaAutoresPelicula))
                    echo "<option value='$fila->idActor' selected>$fila->nombre $fila->apellidos</option>";
                else
                    echo "<option value='$fila->idActor'>$fila->nombre $fila->apellidos</option>";
            }
            echo "</select>";

            // Por último, un enlace para crear un nuevo autor
            echo "<a href='index.php?action=formularioInsertarAutores'>Añadir nuevo</a><br>";

            // Finalizamos el formulario
            echo "  <input type='hidden' name='action' value='modificarPelicula'>
                    <input type='submit'>
                  </form>";
            echo "<p><a href='index.php'>Volver</a></p>";

            break;


            
            // --------------------------------- MODIFICAR LIBROS ----------------------------------------

        case "modificarPelicula":
            echo "<h1>Modificación de Peliculas</h1>";

            // Vamos a procesar el formulario de modificación de libros
            // Primero, recuperamos todos los datos del formulario
            $idPelicula = $_REQUEST["idPelicula"];
            $titulo = $_REQUEST["titulo"];
            $genero = $_REQUEST["genero"];
            $pais = $_REQUEST["pais"];
            $ano = $_REQUEST["ano"];
            $autores = $_REQUEST["autor"];

            // Lanzamos el UPDATE contra la base de datos.
            $db->query("UPDATE peliculas SET
							titulo = '$titulo',
							genero = '$genero',
							pais = '$pais',
							ano = '$ano',
							WHERE idPelicula = '$idPelicula'");

            if ($db->affected_rows == 1) {
                // Si la modificación del libro ha funcionado, continuamos actualizando la tabla "escriben".
                // Primero borraremos todos los registros del libro actual y luego los insertaremos de nuevo
                $db->query("DELETE FROM actuan WHERE idPeli = '$idPelicula'");
                // Ya podemos insertar todos los autores junto con el libro en "escriben"
                foreach ($autores as $idActor) {
                    $db->query("INSERT INTO actuan(idPeli, idActor) VALUES('$idPelicula', '$idActor')");
                }
                echo "Pelicula actualizada con éxito";
            } else {
                // Si la modificación del libro ha fallado, mostramos mensaje de error
                echo "Ha ocurrido un error al modificar la pelicula. Por favor, inténtelo más tarde.";
            }
            echo "<p><a href='index.php'>Volver</a></p>";
            break;


            // --------------------------------- ACTION NO ENCONTRADA ----------------------------------------

        default:
            echo "<h1>Error 404: página no encontrada</h1>";
            echo "<a href='index.php'>Volver</a>";
            break;
          /*  */
    } // switch

    ?>

</body>

</html>