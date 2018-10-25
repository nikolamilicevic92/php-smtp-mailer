<?php

require __DIR__ . '/SMTPSocket.php';

/**
 * This class is used for sending emails through the smtp server.
 * 
 * @class
 */
class Mail
{
  /**
   * @var array
   */
  private $config = [
    'charset'  => 'UTF-8',
    'encoding' => '7bit',
    'username' => '',
    'password' => '',
    //Can be 'example@gmail.com or ['Jhon Doe', 'example@gmail.com']
    'from'     => '',
    'to'       => [],
    'cc'       => [],
    'replyTo'  => null,
    'subject'  => 'No subject',
    'host'     => 'smtp.gmail.com',
    'port'     => 465,
    'verbose'  => true,
    'text'     => '',
    'html'     => null
  ];

  /**
   * @var array
   */
  private $headers = [];


  /**
   * @constructor
   */
  public function __construct(array $config)
  {
    $this->config = array_merge($this->config, $config);
  }


  public function __get(string $key)
  {
    return $this->config[$key];
  }


  /**
   * Bootstraping and sending the mail.
   * 
   * @return void
   */
  public function send(): void
  {
    (new SMTPSocket($this->verbose))

      ->connectTo($this->host, $this->port)

      ->authenticate($this->username, $this->password)

      ->setFrom(is_array($this->from) ? $this->from[1] : $this->from)

      ->addRecepients($this->to)

      ->sendData($this->getEmailBody());

  }


  /**
   * Setting the appropriate headers and content based on set properties.
   * 
   * @return string $emailBody
   */
  private function getEmailBody(): string
  {
    $this->setBaseHeaders();

    $this->setRecepientsHeaders();

    if($this->html) {
      $this->setTextAndHTMLHeaders();
    } else {
      $this->setTextHeaders();
    }

    return implode("\r\n", $this->headers);
  }


  /**
   * Setting headers and content for sending raw text.
   * 
   * @return void
   */
  private function setTextHeaders(): void
  {
    $this->addHeaders(
      'Content-Type: text/plain; charset="'. $this->charset. '"',
      "Content-Transfer-Encoding: {$this->encoding}",
      '',
      $this->text
    );
  }


  /**
   * Setting html and text headers in case that email client cannot open html.
   * 
   * @return void
   */
  private function setTextAndHTMLHeaders(): void
  {
    $boundary = uniqid();

    $this->addHeaders(
      'MIME-Version: 1.0',
      'Content-Type: multipart/alternative; boundary="'. $boundary .'"',
      '',
      "--{$boundary}",
      //Adding raw text
      "Content-type: text/plain; charset={$this->charset}",
      "Content-Transer-Encoding: {$this->encoding}",
      '',
      $this->text,
      "--{$boundary}",
      //Adding HTML
      'Content-Type: text/html; charset="'. $this->charset .'"',
      "Content-Transfer-Encoding: {$this->encoding}",
      '',
      $this->html,
      "--{$boundary}--"
    );
  }


  /**
   * Setting From, Reply-To, Subject and Date headers.
   * 
   * @return void
   */
  private function setBaseHeaders(): void
  {
    if( is_array( $this->from ) ) {

      $this->headers[] = "From: {$this->from[0]} <{$this->from[1]}>";

    } else {

      $this->headers[] = "From: <{$this->from}>";
    }

    if($this->replyTo) {

      $this->headers[] = "Reply-To: <{$this->replyTo}>";

    } else {

      $replyToEmail = is_array($this->from) ? $this->from[1] : $this->from;

      $this->headers[] = "Reply-To: <{$replyToEmail}>";
    }

    $this->addHeaders("Subject: {$this->subject}", 'Date: '. date('m/d/Y H:i'));
  }


  /**
   * Shortcut for populating headers array.
   * 
   * @param mixed $headers
   * 
   * @return void
   */
  private function addHeaders(...$headers): void
  {
    array_walk( $headers, function ($header) {
      
      array_push( $this->headers, $header );

    });
  }


  /**
   * Setting To and CC headers.
   * 
   * @return void
   */
  private function setRecepientsHeaders(): void
  {
    //Setting 'To' header
    $this->headers[] = 'To: '. implode(', ', array_map( function ($recepient) {

      return "<{$recepient}>";

    }, $this->config['to']));
    
    //Setting 'CC' header
    if(count($this->config['cc'])) {

      $this->headers[] = 'CC: '. implode(', ', array_map( function ($recepient) {

        return "<{$recepient}>";
        
      }, $this->config['cc']));
    }
  }


  /**
   * Setting the sender's email address if one argument is provided, or both the
   * sender's name and email address if two arguments are provided.
   * 
   * @param string $arg1 | $name or $email
   * @param mixed  $arg2 | $email
   * 
   * @return Mail $this
   */
  public function setFrom(string $arg1, $arg2 = null): Mail
  {
    $this->config['from'] = $arg2 ? [$arg1, $arg2] : $arg1;
    
    return $this;
  }


  /**
   * Setting the optional reply-to. By default it will be set to sender's email.
   * 
   * @param string $replyTo
   * 
   * @return Mail $this
   */
  public function setReplyTo(string $replyTo): Mail
  {
    $this->config['reply-to'] = $replyTo;

    return $this;
  }


  /**
   * Adding recepients of email. Input can be either comma separated list of
   * emails or an array of emails.
   * 
   * @param mixed $recepients
   * 
   * @return Mail $this
   */
  public function setTo(...$recepients): Mail
  {
    $this->config['to'] = is_array($recepients[0]) ? $recepients[0] : $recepients; 

    return $this;
  }


  /**
   * Setting CC.
   * 
   * @see setTo()
   */
  public function setCC(...$recepients): Mail
  {
    $this->config['cc'] = is_array($recepients[0]) ? $recepients[0] : $recepients;

    return $this;
  }


  /**
   * Setting the mail's subject.
   * 
   * @param string $subject
   * 
   * @return Mail $this
   */
  public function setSubject(string $subject): Mail
  {
    $this->config['subject'] = $subject;

    return $this;
  }


  /**
   * Setting the raw text that will be displayed if email client does not 
   * support html emails or if no html is set.
   * 
   * @param string $text
   * 
   * @return Mail $this
   */
  public function setText(string $text): Mail
  {
    $this->config['text'] = $text;

    return $this;
  }


  /**
   * Setting the HTML part of the email.
   * 
   * @param string $html
   * 
   * @return Mail $this
   */
  public function setHTML(string $html): Mail
  {
    $this->config['html'] = $html;

    return $this;
  }


  /**
   * Loading the html template and replacing template variables with those
   * provided in the data array.
   * 
   * @param string $pathToTemplate
   * @param array  $templateData
   * 
   * @return Mail $this
   */
  public function loadTemplate(string $pathToTemplate, array $templateData): Mail
  {
    $compiledTemplate = preg_replace_callback(
      //Pattern is {{ $variable }}
      '/{{\s*\$([^\}\s]+)\s*}}/', 
      //Replacement
      function($matches) use ($templateData) {
        return $templateData[$matches[1]] ?? '';
      }, 
      //Template
      file_get_contents($pathToTemplate)
    );
    
    return $this->setHTML($compiledTemplate);
  }
}