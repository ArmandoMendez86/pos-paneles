<?php
date_default_timezone_set('America/Mexico_City');
require 'model.php';

class Producto extends Model


{

    protected $table = 'producto';

    public function venderproductos($data)
    {
        $columns = array_keys($data[0]);
        $columns = implode(', ', $columns);
        $values = [];
        foreach ($data as $item) {
            $escaped_values = array_map(function ($value) {
                return mysqli_real_escape_string($this->conection, $value);
            }, $item);
            $values[] = "'" . implode("', '", $escaped_values) . "'";
        }
        $values = implode('), (', $values);
        $sql = "INSERT INTO venta_producto ({$columns}) VALUES ({$values})";
        $this->conection->query($sql);
        return null;
    }

    public function venderservicio($data)
    {
        $columns = array_keys($data);
        $columns = implode(', ', $columns);
        $escaped_values = array_map(function ($value) {
            return mysqli_real_escape_string($this->conection, $value);
        }, $data);

        $values = "'" . implode("', '", $escaped_values) . "'";
        $sql = "INSERT INTO venta_servicio ({$columns}) VALUES ({$values})";
        $this->conection->query($sql);
        return null;
    }

    public function obtenerVentasProductos($idempleado, $idrol)
    {
        if ($idrol == 1) {
            $sql = "SELECT
            producto.pro_serv,
            producto.unidad,
            SUM(venta_producto.cantidad) AS total_cantidad,
            empleado.nombre AS nombre_empleado,
            venta_producto.fecha,
            SUM(producto.p_d * venta_producto.cantidad) AS total_subtotal
            FROM venta_producto
            INNER JOIN producto ON venta_producto.p_s = producto.id
            INNER JOIN empleado ON venta_producto.idempleado = empleado.id
            GROUP BY producto.pro_serv, producto.unidad, empleado.nombre, DATE(venta_producto.fecha)";
            $stmt = $this->conection->prepare($sql);
            $stmt->execute();
            $results = $stmt->get_result();
            return $results->fetch_all(MYSQLI_ASSOC);
        } else {
            $sql = "SELECT
            producto.pro_serv,
            producto.unidad,
            SUM(venta_producto.cantidad) AS total_cantidad,
            empleado.nombre AS nombre_empleado,
            venta_producto.fecha,
            SUM(producto.p_d * venta_producto.cantidad) AS total_subtotal
            FROM venta_producto
            INNER JOIN producto ON venta_producto.p_s = producto.id
            INNER JOIN empleado ON venta_producto.idempleado = empleado.id
            WHERE venta_producto.idempleado = '$idempleado'
            GROUP BY producto.pro_serv, producto.unidad, empleado.nombre, DATE(venta_producto.fecha)";
            $stmt = $this->conection->prepare($sql);
            $stmt->execute();
            $results = $stmt->get_result();
            return $results->fetch_all(MYSQLI_ASSOC);
        }
    }

    public function ventasProductosAll()
    {
        $sql = "SELECT venta_producto.id, venta_producto.p_s, producto.pro_serv, producto.unidad, venta_producto.cantidad, empleado.nombre AS nombre_empleado, venta_producto.fecha,  
        producto.precio * venta_producto.cantidad AS subtotal
        FROM venta_producto
        INNER JOIN producto ON venta_producto.p_s = producto.id
        INNER JOIN empleado ON venta_producto.idempleado = empleado.id";
        $stmt = $this->conection->prepare($sql);
        $stmt->execute();
        $results = $stmt->get_result();
        return $results->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerVentasServicios()
    {
        $sql = "SELECT venta_servicio.id AS venta_servicio_id, venta_servicio.p_s, 
        producto.pro_serv, cliente.id AS cliente_id, cliente.nombre AS nombre_cliente, 
        cliente.ap AS ap_cliente, venta_servicio.fecha, 
        empleado.nombre AS nombre_empleado, producto.p_d * venta_servicio.cantidad AS subtotal FROM venta_servicio 
        INNER JOIN producto ON venta_servicio.p_s = producto.id 
        INNER JOIN cliente ON venta_servicio.idcliente = cliente.id 
        INNER JOIN empleado ON venta_servicio.idempleado = empleado.id";
        $stmt = $this->conection->prepare($sql);
        $stmt->execute();
        $results = $stmt->get_result();
        return $results->fetch_all(MYSQLI_ASSOC);
    }

    public function actualizarAlmacen($data)
    {
        foreach ($data as $item) {
            $p_s = $item['p_s'];
            $cantidad = $item['cantidad'];
            $sql = "UPDATE producto SET cantidad = cantidad - ? WHERE id = ?";
            $stmt = $this->conection->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('ii', $cantidad, $p_s);
                $stmt->execute();
            } else {
                error_log("Error de preparación de la consulta: " . $this->conection->error);
            }
        }
        $stmt->close();
        return true;
    }

    public function totalVentasXdiaServicios()
    {
        $fechaHoy = date("Y-m-d");
        $sql = "SELECT SUM(producto.p_d * venta_servicio.cantidad) AS total
        FROM venta_servicio
        INNER JOIN producto ON venta_servicio.p_s = producto.id
        WHERE DATE(venta_servicio.fecha) = '$fechaHoy'";
        $stmt = $this->conection->prepare($sql);
        $stmt->execute();
        $results = $stmt->get_result();
        return $results->fetch_all(MYSQLI_ASSOC);
    }
    public function totalVentasXdiaProductos()
    {
        $fechaHoy = date("Y-m-d");
        $sql = "SELECT  SUM( producto.p_d * venta_producto.cantidad) AS total
        FROM venta_producto
        INNER JOIN producto ON venta_producto.p_s = producto.id
        WHERE DATE(venta_producto.fecha) = '$fechaHoy'";
        $stmt = $this->conection->prepare($sql);
        $stmt->execute();
        $results = $stmt->get_result();
        return $results->fetch_all(MYSQLI_ASSOC);
    }

    public function actualizarFechaServicio($data)
    {
        $sql = "UPDATE venta_servicio SET p_s = ?, fecha = ?, idempleado = ?, vence = ?, couch = ?, fventa= ?, fperso= ?, finperso= ? WHERE id = ?";
        $stmt = $this->conection->prepare($sql);
        $fecha = $data['fecha'];
        $idempleado = $data['idempleado'];
        $vence = $data['vence'];
        $couch = $data['couch'];
        $fventa = $data['fventa'];
        $fperso = $data['fperso'];
        $finperso = $data['finperso'];
        $p_s = $data['p_s'];
        $id = $data['id'];
        $stmt->bind_param('isisssssi', $p_s, $fecha, $idempleado, $vence, $couch, $fventa, $fperso, $finperso, $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public function eliminarVentaServicio($id)
    {
        $sql = "DELETE FROM venta_servicio WHERE id = ?";
        $stmt = $this->conection->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }
    public function eliminarVentaProducto($id)
    {
        $sql = "DELETE FROM venta_producto WHERE id = ?";
        $stmt = $this->conection->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function restablecerAlmacen($data)
    {
        $sql = "UPDATE producto SET cantidad = cantidad + ? WHERE id = ?";
        $stmt = $this->conection->prepare($sql);
        $p_s = $data['p_s'];
        $cantidad = $data['cantidad'];
        $stmt->bind_param('ii', $cantidad, $p_s);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public function resumenVentasXdia()
    {
        $fechaHoy = date("Y-m-d");
        /* $fechaHoy = '2023-12-14'; */
        $sql = " SELECT 
        producto.pro_serv, 
        producto.unidad, 
        producto.compra, 
        producto.p_d AS precio, 
        SUM(vp.cantidad) AS total_cantidad, 
        SUM(producto.p_d * vp.cantidad) AS total_subtotal,
        SUM((producto.p_d - producto.compra) * vp.cantidad) AS ganancia,
        producto.cantidad, 
        vp.fecha 
        FROM ( 
        SELECT p_s, cantidad, fecha FROM venta_producto 
        UNION ALL SELECT p_s, cantidad, fecha FROM venta_servicio ) AS vp 
        INNER JOIN producto ON vp.p_s = producto.id 
        GROUP BY producto.pro_serv, producto.unidad, producto.p_d, DATE(vp.fecha)";
        $stmt = $this->conection->prepare($sql);
        $stmt->execute();
        $results = $stmt->get_result();
        return $results->fetch_all(MYSQLI_ASSOC);
    }

    public function createRegalia($data)
    {
        $columns = array_keys($data);
        $columns = implode(', ', $columns);
        $escaped_values = array_map(function ($value) {
            return mysqli_real_escape_string($this->conection, $value);
        }, $data);
        $values = "'" . implode("', '", $escaped_values) . "'";
        $sql = "INSERT INTO regalia ({$columns}) VALUES ({$values})";
        $this->conection->query($sql);
    }

    public function obtenerRegalias()
    {
        $sql = "SELECT producto.pro_serv, producto.unidad, regalia.cantidad, empleado.nombre AS nombre_empleado, regalia.fecha, regalia.persona
        FROM regalia
        INNER JOIN producto ON regalia.p_s = producto.id
        INNER JOIN empleado ON regalia.idempleado = empleado.id";
        $stmt = $this->conection->prepare($sql);
        $stmt->execute();
        $results = $stmt->get_result();
        return $results->fetch_all(MYSQLI_ASSOC);
    }

    public function ventaServicioXidCliente($id)
    {
        $sql = "SELECT producto.pro_serv, DATE(venta_servicio.vence) AS vence, cliente.nombre, cliente.gen, cliente.id, cliente.imagen
        FROM venta_servicio
        INNER JOIN producto ON venta_servicio.p_s = producto.id
        INNER JOIN cliente ON venta_servicio.idcliente = cliente.id
        WHERE venta_servicio.idcliente = '$id'";
        $stmt = $this->conection->prepare($sql);
        $stmt->execute();
        $results = $stmt->get_result();
        return $results->fetch_all(MYSQLI_ASSOC);
    }
}
