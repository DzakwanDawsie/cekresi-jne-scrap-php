<?php 

class CekResiJne {
  private $dom, $raw, $id, $endpoint = 'https://cekresi.jne.co.id', $error = false;

  function __construct($id){
    $this->dom = new DOMDocument();
    $this->id = $id;

    libxml_use_internal_errors(true);

    $curl = curl_init($this->endpoint.'/'.$id);
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,"POST");
    curl_setopt($curl,CURLOPT_HTTPHEADER,[
      'Referer: https://www.jne.co.id/id/beranda'
    ]);

    $curlResult = curl_exec($curl);
    $curlErr = curl_error($curl);
    curl_close($curl);

    $this->error = (!empty($curlErr));

    $this->raw = $curlResult;

    if (!$this->error) $this->dom->loadHTML($curlResult); 
  }

  public function getRaw(){
    return (!$this->error) ? $this->raw : null;
  }

  public function getAllData(){
    if (!$this->validate()) return [];

    $results = (object) array_merge(
      (array) $this->getGeneralInfo(),
      (array) $this->getShipmentDetail(),
      ['histories' => $this->getHistories()]
    );

    return $results;
  }

  public function getGeneralInfo(){
    if (!$this->validate()) return [];

    $domPath = new DomXPath($this->dom);
    $nodes = $domPath->query("//div[@class='row tile_count']/div/div");
  
    list($service,$from,$to,$estimation,$podDate) = $this->fetchNodes($nodes);

    $result = (object) [
      'service' => $service,
      'from' => $from,
      'to' => $to,
      'estimation' => $estimation,
      'pod_date' => $podDate,
    ];

    return $result;
  }

  public function getHistories(){
    if (!$this->validate()) return [];

    $domPath = new DomXPath($this->dom);
    $counter = count($domPath->query("//div[@class='block_content']"));

    $descriptionNodes = $domPath->query("//div[@class='block_content']/h2/a");
    $timestampNodes = $domPath->query("//div[@class='block_content']/div/span");

    $results = [];

    for ($i=0; $i < $counter; $i++) { 
      $description = $descriptionNodes->item($i)->nodeValue;
      $timestamp = $timestampNodes->item($i)->nodeValue;

      $results[] = (object) [
        'description' => $description,
        'timestamp' => $timestamp
      ];
    }

    return $results;
  }

  public function getShipmentDetail(){
    if (!$this->validate()) return [];

    $domPath = new DomXPath($this->dom);
    $nodes = $domPath->query("//div[@class='x_panel']/div/h4/b");

    list(
      $shipmentDate,
      $koli,
      $weight,
      $goodDescription,
      $shipperName,
      $shipperCity,
      $receiverName,
      $receiverCity
    ) = $this->fetchNodes($nodes);

    $results = (object) [
      'shipment_date' => $shipmentDate,
      'koli' => $koli,
      'weight' => $weight,
      'good_description' => $goodDescription,
      'shipper' => (object) [
        'name' => $shipperName,
        'city' => $shipperCity
      ],
      'receiver' => (object) [
        'name' => $receiverName,
        'city' => $receiverCity
      ]
    ];

    return $results;
  }

  public function fetchNodes($nodes){
    $results = [];

    for ($i=0; $i < count($nodes); $i++) { 
      $results[] = $nodes->item($i)->nodeValue;
    }

    return $results;
  }

  public function validate(){
    if ($this->error) return false;
    
    $domPath = new DomXPath($this->dom);
    $nodes = $domPath->query("//i[@class='fa fa-paper-plane-o']");

    return (count($nodes) > 0);
  }
}
