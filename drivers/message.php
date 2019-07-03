<?php

class message
{   
  const CREADO = 1;
  const ERROR = 2;
  public static function post($peticion)
  {
    
    $body = file_get_contents('php://input');
    $messagebody = json_decode($body);
    
    $email = $messagebody->email;
    $message = $messagebody->message;
    $roomName = $messagebody->nameRoom;
    $tmp = $email;
    $ret = 0;

    if(empty($email))
      $ret = self::create_message_root($roomName, $message); 
    else
      $ret = self::create_message_invited($tmp, $roomName, $message);

    if($ret == self::CREADO)
    {  
      return ["Mensaje" => "El mensaje fue enviado"];
    }
    else if($ret == self::ERROR)
    {
      http_response_code(400);
      return ["Mensaje" => "Error al enviar el mensaje"];
    }
    else
    {
      http_response_code(400);
      return ["Mensaje" => $ret];
    }
  }

  private function create_message_root($nameRoom, $message)
  {
    $idUser = usuarios::autorizar();
    $idRoom = room::getIDROOM($idUser, $nameRoom);
    if($idRoom > 0)
    {
      $cmd = "INSERT INTO Message VALUES(null,?,?,?)";
      $st = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($cmd);
      $st->bindParam(1, $message);
      $st->bindParam(3, $idUser, PDO::PARAM_INT);
      $st->bindParam(2, $idRoom, PDO::PARAM_INT);
      if($st->execute())
        return self::CREADO;
      return self::ERROR;
    }
    else
    {
      return "EL ".$nameRoom." no existe";
    }
  }
  /*
   * email => imail del lider del grupo
   * nameRoom $message
   *
   * */
  private function create_message_invited($email, $nameRoom, $message)
  {
    $idUser = usuarios::autorizar();
    $cmd = "SELECT idUser FROM User INNER JOIN Email ON ".
           "Email.idEmail=User.idEmail ".
           "WHERE Email.email=?";
      $st = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($cmd);
    $st->bindParam(1, $email);
    $st->execute();
    $idUserContact = $st->fetchColumn();
    if($idUserContact > 0)
    {
      $idRoom = room::getIDROOM($idUserContact, $nameRoom); 
      
      /*********************************/
      $ret = self::revisar($idUserContact, $idUser, $idRoom); 
      if($ret == 0)
        return "No esta incluido en el grupo ".$nameRoom. " de ".$email;   
      if($idRoom > 0)
      {
        $cmd = "INSERT INTO Message VALUES(null,?,?,?)";
        $st = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($cmd);
        $st->bindParam(1, $message);
        $st->bindParam(2, $idRoom, PDO::PARAM_INT);
        $st->bindParam(3, $idUser, PDO::PARAM_INT);
        if($st->execute())
          return self::CREADO;
        return self::ERROR;
      }
      return "El ROOM ".$nameRoom." NO EXITE PARA ".$email; 
    }
    return self::ERROR;
  }
  private function revisar($idUser, $idUserContact, $idRoom)
  {
    $cmd = "SELECT COUNT(*) FROM Groups INNER JOIN Contact ON ".
           "Contact.idContact=Groups.idContact WHERE ".
           "Contact.idUser=? AND Contact.idUserContact=? AND ".
           "Groups.idRoom=?";

    $st = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($cmd);
    $st->bindParam(1, $idUser, PDO::PARAM_INT);
    $st->bindParam(2, $idUserContact, PDO::PARAM_INT);
    $st->bindParam(3, $idRoom, PDO::PARAM_INT);
    if($st->execute())
      return $st->fetchColumn();
    else
      return 0;
  }
}

