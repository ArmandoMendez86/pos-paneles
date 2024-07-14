<?php
require 'model.php';


class Cliente extends Model
{
    protected $table = 'cliente';


    public function createUser($data)
    {
        if (isset($data['password'])) {
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['password'] = $hashed_password;
        }

        // Manejar la subida de la imagen
        $uploadDir = '../clientes/images/';
        $uploadFile = $uploadDir . basename($data['imagen']['name']);
        move_uploaded_file($data['imagen']['tmp_name'], $uploadFile);
        //$data['imagen'] = $uploadFile; // Agregar la ruta de la imagen a los datos
        $data['imagen'] = basename($data['imagen']['name']); // Agregar la ruta de la imagen a los datos

        $columns = array_keys($data);
        $columns = implode(', ', $columns);

        $escaped_values = array_map(function ($value) {
            return mysqli_real_escape_string($this->conection, $value);
        }, $data);

        $values = "'" . implode("', '", $escaped_values) . "'";
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})";
        $this->conection->query($sql);

        /*   $ultimo = $this->conection->insert_id;
        return $this->findById($ultimo); */
    }

    public function updateUser($data)
    {

        // Manejar la subida de la nueva imagen si se proporciona
        if (!empty($data['imagen'])) {
            $uploadDir = '../clientes/images/';
            $uploadFile = $uploadDir . basename($data['imagen']['name']);
            move_uploaded_file($data['imagen']['tmp_name'], $uploadFile);
            $data['imagen'] = basename($data['imagen']['name']); // Actualizar la ruta de la imagen en los datos
        }
        if ($data['imagen'] == null) {
            unset($data['imagen']);
        }


        $columns = array_keys($data);
        $columns = implode(', ', $columns);

        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }

        $id = $data['id'];
        $params[] = intval($id);

        $fields = implode(', ', $fields);

        $sql = "UPDATE {$this->table} SET {$fields} WHERE id = ?";
        $stmt = $this->conection->prepare($sql);

        if ($stmt) {
            $types = str_repeat('s', count($params) - 1) . 'i';
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        } else {
            error_log("Error de preparación de la consulta: " . $this->conection->error);
            return null;
        }
    }
}
