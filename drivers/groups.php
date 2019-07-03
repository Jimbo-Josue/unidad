<?php

class groups
{
  public static function post($peticion)
  {
    $body = file_get_contents('php://input');
    $group = json_decode($body);

    $idUser = usuarios::autorizar();
    $nameRoom = $group->nameRoom;
    $email = $group->email;

    $ret = room::verificar($idUser, $nameRoom);
    if($ret == 0)
    {
      http_response_code(400);
      return ["Mensaje" => "No existe el ROOM ".$nameRoom];
    }
    $idRoom = room::getIDROOM($idUser, $nameRoom);
    $idContact = self::verificar_usuario_contacto($idUser, $email);
    if($idContact == 0)
    {
      http_response_code(400);
      return ["Mensaje" => "No tiene agregado a ".$email];
    }

    $ret = self::verificar_repeticion($idContact, $idRoom);

    if($ret > 0)
    {
      http_response_code(400);
      return ["Mensaje" => "El usuario ya esta dentro de ".$nameRoom];
    }

    $cmd = "INSERT INTO Groups VALUES (null,?,?)";
    $st = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($cmd);
    $st->bindParam(1, $idContact, PDO::PARAM_INT);
    $st->bindParam(2, $idRoom, PDO::PARAM_INT);

    if($st->execute())
      return ["Mensaje" => "El contacto fue agregado"];
    
    http_response_code(400);
    return ["Mensaje" => "ERROR DESCONOCIDO AL AGREGAR EL CONTACTO"];
  }

  private function verificar_repeticion($idContact, $idRoom)
  {
    $cmd = "SELECT COUNT(*) FROM Groups WHERE idContact=? AND idRoom=?";
    $st = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($cmd);
    $st->bindParam(1, $idContact, PDO::PARAM_INT);
    $st->bindParam(2, $idRoom, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchColumn();   
  }

  /* Id CONTACTO */
  private function verificar_usuario_contacto($idUser, $email)
  {
    $cmd = "SELECT Contact.idContact FROM Contact INNER JOIN User ON ".
      "User.idUser=Contact.idUserContact INNER JOIN Email ON ".
      "Email.idEmail=User.idEmail ".
      "WHERE Contact.idUser=? AND Email.email=?";
    $st = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($cmd);
    $st->bindParam(1, $idUser, PDO::PARAM_INT);
    $st->bindParam(2, $email);
    $st->execute();
    return $st->fetchColumn();
  }
}

