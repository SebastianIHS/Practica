<?php
// Incluir archivo de verificación de sesión
require_once '../config/verificar_sesion.php';

// Incluir archivo de conexión
require_once '../config/db_connect.php';

// Verificar si se ha solicitado eliminar el avatar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["eliminar_avatar"])) {
    $usuario_id = $_SESSION['usuario_id'];
    
    // Actualizar la base de datos para usar el avatar predeterminado
    $sql = "UPDATE usuario SET avatar = NULL WHERE id_usuario = '$usuario_id'";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: perfil.php?mensaje=" . urlencode("Avatar eliminado correctamente. Se usarán tus iniciales como avatar.") . "&tipo=success");
    } else {
        header("Location: perfil.php?mensaje=" . urlencode("Error al eliminar el avatar: " . mysqli_error($conn)) . "&tipo=danger");
    }
    exit();
}

// Verificar si se ha enviado un archivo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] == 0) {
    
    $usuario_id = $_SESSION['usuario_id'];
    
    // Validar el tipo de archivo
    $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png"];
    $filename = $_FILES["avatar"]["name"];
    $filetype = $_FILES["avatar"]["type"];
    $filesize = $_FILES["avatar"]["size"];
    
    // Verificar la extensión del archivo
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if (!array_key_exists($ext, $allowed)) {
        header("Location: perfil.php?mensaje=" . urlencode("Error: Por favor selecciona un formato válido de imagen (JPG, JPEG, PNG, GIF)") . "&tipo=danger");
        exit();
    }
    
    // Verificar el tipo MIME del archivo
    if (!in_array($filetype, $allowed)) {
        header("Location: perfil.php?mensaje=" . urlencode("Error: Por favor selecciona un formato válido de imagen") . "&tipo=danger");
        exit();
    }
    
    // Verificar tamaño del archivo - máximo 5MB
    if ($filesize > 5242880) {
        header("Location: perfil.php?mensaje=" . urlencode("Error: El archivo es demasiado grande. Tamaño máximo: 5MB") . "&tipo=danger");
        exit();
    }
    
    // Crear carpeta de avatares si no existe
    $upload_dir = "../Image/avatares/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generar nombre único para evitar sobreescrituras
    $new_filename = $usuario_id . '_' . time() . '_' . $filename;
    $upload_path = $upload_dir . $new_filename;
    
    // Leer el archivo en una variable
    $imageData = file_get_contents($_FILES["avatar"]["tmp_name"]);
    
    // Escapar los datos binarios para la consulta SQL
    $imageData = mysqli_real_escape_string($conn, $imageData);
    
    // Actualizar la base de datos con la imagen como BLOB
    $sql = "UPDATE usuario SET 
            avatar = '$imageData', 
            avatar_tipo = '$filetype' 
            WHERE id_usuario = '$usuario_id'";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: perfil.php?mensaje=" . urlencode("¡Avatar actualizado correctamente!") . "&tipo=success");
    } else {
        header("Location: perfil.php?mensaje=" . urlencode("Error al actualizar la base de datos: " . mysqli_error($conn)) . "&tipo=danger");
    }
} else {
    header("Location: perfil.php?mensaje=" . urlencode("No se seleccionó ningún archivo o hubo un error en la carga") . "&tipo=warning");
}

exit();
?>