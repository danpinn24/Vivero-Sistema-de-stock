<?php
require_once __DIR__ . '/../models/Producto.php';

class ProductoController {
    private function volver($mensaje, $tipo='success'): void {
        header('Location: index.php?action=inventario&tipo='.urlencode($tipo).'&mensaje='.urlencode($mensaje)); exit;
    }
    private function validarCategoria(string $categoria): bool {
        return in_array($categoria, ['Plantas y Flores','Árboles Frutales','Macetas y Jardineras','Sustratos y Tierra','Herramientas e Insumos'], true);
    }
    private function subirImagen(string $actual='default.jpg'): string {
        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) return $actual ?: 'default.jpg';
        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('No se pudo subir la imagen.');
        if ($_FILES['imagen']['size'] > 5 * 1024 * 1024) throw new RuntimeException('La imagen no puede superar los 5 MB.');
        $tmp=$_FILES['imagen']['tmp_name']; $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $extensiones=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!isset($extensiones[$mime])) throw new RuntimeException('Formato de imagen no válido. Usá JPG, PNG o WEBP.');
        $nombre=bin2hex(random_bytes(16)).'.'.$extensiones[$mime];
        $dir=__DIR__.'/../assets/img/uploads/';
        if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
        if (!move_uploaded_file($tmp,$dir.$nombre)) throw new RuntimeException('No se pudo guardar la imagen.');
        return $nombre;
    }
    public function index(): void {
        $productos=(new Producto())->obtenerTodos();
        require __DIR__.'/../views/layouts/header.php'; require __DIR__.'/../views/productos/inventario.php'; require __DIR__.'/../views/layouts/footer.php';
    }
    public function registrar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=inventario'); exit; }
        try {
            $categoria=trim($_POST['categoria'] ?? ''); $nombre=trim($_POST['nombre'] ?? '');
            $stock=filter_var($_POST['stock'] ?? null,FILTER_VALIDATE_INT); $min=filter_var($_POST['stock_minimo'] ?? null,FILTER_VALIDATE_INT); $precio=filter_var($_POST['precio'] ?? null,FILTER_VALIDATE_FLOAT);
            if (!$this->validarCategoria($categoria) || $nombre==='' || mb_strlen($nombre)>120 || $stock===false || $stock<0 || $min===false || $min<0 || $precio===false || $precio<0) throw new RuntimeException('Revisá los datos: categoría, nombre, stock y precio son obligatorios y deben ser válidos.');
            $imagen=$this->subirImagen(); $ubicacion=trim($_POST['ubicacion'] ?? '') ?: 'Depósito General';
            (new Producto())->insertar($categoria,$nombre,$stock,$min,$precio,$imagen,$ubicacion);
            $this->volver('Producto registrado correctamente.');
        } catch (Throwable $e) { $this->volver($e->getMessage(),'error'); }
    }
    public function actualizarStock(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=inventario'); exit; }
        $id=filter_var($_POST['id'] ?? null,FILTER_VALIDATE_INT); $cantidad=filter_var($_POST['cantidad'] ?? null,FILTER_VALIDATE_INT); $tipo=$_POST['tipo'] ?? '';
        if (!$id || !$cantidad || $cantidad<1) $this->volver('Ingresá un producto y una cantidad válida.','error');
        $model=new Producto();
        if ($tipo==='reponer') $ok=$model->reponerStock($id,$cantidad);
        elseif ($tipo==='descontar') $ok=$model->descontarStock($id,$cantidad);
        else $this->volver('Tipo de movimiento no válido.','error');
        if (!$ok) $this->volver($tipo==='descontar'?'No hay stock suficiente o el producto ya no existe.':'No se encontró el producto para actualizar.','error');
        $this->volver($tipo==='reponer'?'Stock repuesto correctamente.':'Venta registrada y stock actualizado.');
    }
    public function editar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=inventario'); exit; }
        try {
            $id=filter_var($_POST['id'] ?? null,FILTER_VALIDATE_INT); $categoria=trim($_POST['categoria'] ?? ''); $nombre=trim($_POST['nombre'] ?? '');
            $stock=filter_var($_POST['stock'] ?? null,FILTER_VALIDATE_INT); $min=filter_var($_POST['stock_minimo'] ?? null,FILTER_VALIDATE_INT); $precio=filter_var($_POST['precio'] ?? null,FILTER_VALIDATE_FLOAT);
            $model=new Producto(); $actual=$model->obtenerPorId($id);
            if (!$actual || !$this->validarCategoria($categoria) || $nombre==='' || mb_strlen($nombre)>120 || $stock===false || $stock<0 || $min===false || $min<0 || $precio===false || $precio<0) throw new RuntimeException('No se pudo modificar: revisá los datos ingresados.');
            $imagen=$this->subirImagen($actual['imagen'] ?? 'default.jpg'); $ubicacion=trim($_POST['ubicacion'] ?? '') ?: 'Depósito General';
            $model->actualizar($id,$categoria,$nombre,$stock,$min,$precio,$imagen,$ubicacion);
            $this->volver('Producto modificado correctamente.');
        } catch (Throwable $e) { $this->volver($e->getMessage(),'error'); }
    }
    public function eliminar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?action=inventario'); exit; }
        $id=filter_var($_POST['id'] ?? null,FILTER_VALIDATE_INT);
        if (!$id || !(new Producto())->eliminar($id)) $this->volver('No se pudo eliminar: el producto no existe.','error');
        $this->volver('Producto eliminado correctamente.');
    }
}
