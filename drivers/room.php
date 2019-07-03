<?php

class room
{
  public static function post($peticion)
  {
    $body = file_get_contents('php://input');
    $room = json_decode($body);

    $idUser = usuarios::autorizar();
    $name = $room->nombre;

    $ret = self::verificar($idUser, $name);

    if($ret > 0)
    {
      http_response_code(400);
      return ["mensaje" => "El nombre de esa sala ya existe"];
    }
    $cmd = "INSERT INTO Room VALUES (null,?,?)";
    $st = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($cmd);
    $st->bindParam(1, $idUser, PDO::PARAM_INT);
    $st->bindParam(2, $name);

    if($st->execute())
      return ["mensaje" => "Grupo Creado", 
              "idRoom" => self::getIDROOM($idUser, $name)];
    else
      return ["mensaje" => "Error al crear el grupo"];
  }

  public static function get($peticion)
  {
    $idUser = usuarios::autorizar();
    if($peticion[0] == 'created')
    {
      return self::obtener_room_creados($idUser);
    }
    else if($peticion[0] == 'register')
    {
      return self::obtener_room_registrados($idUser);
    }
    else
    {
      http_response_code(400);
      return ["Mensaje" => "No se reconoce la peticion"];
    }
  }

  public function getIDROOM($idUser, $name)
  {
    $cmd = "SELECT idRoom FROM Room WHERE idUser=? AND name=?";
    $st = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($cmd);
    $st->bindParam(1, $idUser, PDO::PARAM_INT);
    $st->bindParam(2, $name);
    $st->execute();
    return $st->fetchColumn();
  }
  public function verificar($idUser, $name)
  {
    $cmd = "SELECT count(*) FROM Room WHERE idUser=? AND name=?";
    $st = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($cmd);
    $st->bindParam(1, $idUser);
    $st->bindParam(2, $name);
    $st->execute();
    return $st->fetchColumn();
  }
  private function obtener_room_creados($idUser)
  {
    $cmd = "SELECT name FROM Room WHERE idUser=?";
    $st = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($cmd);
    $st->bindParam(1, $idUser);
    if(!$st->execute())
    {
      http_response_code(401);
      return ["Mensaje" => "Error en la base de datos"];
    }
    http_response_code(200);
    return ["Datos" => $st->fetchAll(PDO::FETCH_ASSOC)];
  }
  private function obtener_room_registrados($idUser)
  {
    $cmd = "SELECT name FROM Room INNER JOIN Groups ON ".
      "Room.idRoom=Groups.idRoom INNER JOIN Contact ON ".
      "Contact.idContact=Groups.idContact ".
      "WHERE Contact.idUserContact=?";
    $st = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($cmd);
    $st->bindParam(1, $idUser);

    if(!$st->execute())
    {
      http_response_code(401);
      return ["Mensaje" => "Error en la base de datos"];
    }
    http_response_code(200);
    return ["Datos" => $st->fetchAll(PDO::FETCH_ASSOC)];  
  }
}

