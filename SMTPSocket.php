<?php

/**
 * This class is a wrapper around socket used to communicate with smtp server.
 * 
 * @class
 */
class SMTPSocket
{
  /**
   * @var bool
   */
  private $verbose;

  /**
   * @var resource
   */
  private $socket;


  /**
   * @param bool $verbose
   */
  public function __construct(bool $verbose = false)
  {
    $this->verbose = $verbose;
  }


  /**
   * Connecting to smtp server.
   * 
   * @param string $host | ex. smtp.gmail.com
   * @param int    $port | 465 for ssl
   * 
   * @return SMTPSocket $this
   */
  public function connectTo(string $host, int $port): SMTPSocket
  {
    $this->socket = fsockopen('ssl://'. $host, $port);
    
    $this->performHandshake();

    return $this;
  }


  /**
   * Executing EHLO command for initial handshake.
   * 
   * @return SMTPSocket $this
   */
  private function performHandshake(): SMTPSocket
  {
    $this->send("EHLO {$_SERVER['HTTP_HOST']}");

    return $this;
  }


  /**
   * Logging in.
   * 
   * @param string $username
   * @param string $password
   * 
   * @return SMTPSocket $this
   */
  public function authenticate(string $username, string $password): SMTPSocket
  {
    $this
      ->send('AUTH LOGIN')
      ->send(base64_encode($username))
      ->send(base64_encode($password));

    return $this;
  }


  /**
   * Executing MAIL FROM command.
   *
   * @param string $senderEmail
   * 
   * @return SMTPSocket $this
   */
  public function setFrom(string $senderEmail): SMTPSocket
  {
    $this->send("MAIL FROM: <{$senderEmail}>");

    return $this;
  }


  /**
   * Executing RCPT TO command.
   * 
   * @param string $receiver
   * 
   * @return SMTPSocket $this
   */
  public function addRecepient(string $receiver): SMTPSocket
  {
    $this->send("RCPT TO: <{$receiver}>");

    return $this;
  }


  /**
   * @see addRecepient()
   * 
   * @param array $recepients
   * 
   * @return SMTPSocket $this
   */
  public function addRecepients($recepients): SMTPSocket
  {
    array_walk($recepients, function ($recepient) {

      $this->addRecepient($recepient);

    });

    return $this;
  }


  /**
   * Sending the actual email data.
   * 
   * @param string $data
   * 
   * @return void
   */
  public function sendData(string $data): void
  {
    $this
      ->send('DATA')
      ->send($data)
      ->send("\r\n.\r\n")
      ->send('QUIT');

    fclose($this->socket);
  }


  /**
   * Sending data through socket to a remote smtp server.
   * 
   * @param string $payload
   * 
   * @return SMTPSocket $this
   */
  public function send(string $payload): SMTPSocket
  {
    fputs($this->socket, $payload . "\r\n");

    if($this->verbose) {
      
      $this->printSentData($payload);
  
      $this->printReceivedData($this->socket);

    }

    return $this;
  }


  /**
   * Printing data sent to the smtp server.
   * 
   * @param string $data
   * 
   * @return void
   */
  private function printSentData(string $data): void
  {
    echo '<p style="font-family:Monospace;color:green;">';
    echo '<b>Sending</b>: <code>'. htmlspecialchars($data) .'</code>';
    echo '</p>';
  }


  /**
   * Printing the response received from smpt server.
   * 
   * @param resource $socket
   * 
   * @return void
   */
  private function printReceivedData($socket): void
  {
    $response = '';

    while($string = fgets($socket, 4096)) {

      $response .= $string;
      
      if(substr($string, 3, 1) === ' ') break;
    }

    echo '<p style="font-family:Monospace;color:blue;">';
    echo '<b>Received</b>: <code>'. htmlspecialchars($response) .'</code>';
    echo '</p>';
  }

}