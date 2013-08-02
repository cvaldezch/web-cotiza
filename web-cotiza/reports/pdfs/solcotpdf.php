<?php
include("../../datos/postgresHelper.php");
require("../../modules/fpdf.php");

$ruc = $_GET['ruc'];
$nro = $_GET['nro'];


class PDF extends FPDF
{
	var $ruc_ = "";
	var $nro_ = "";
	
	var $widths;
	var $aligns;

function addprm($r,$n){
	$this->ruc_ = $r;
	$this->nro_ = $n;
}
 
function SetWidths($w)
{
   $this->widths=$w;
}
 
function SetAligns($a)
{
   $this->aligns=$a;
}
 
function Row($data)
{
   $nb=0;
   for($i=0;$i<count($data);$i++)
      $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
   $h=4*$nb; 
   $this->CheckPageBreak($h);
   $algs = 'L';

   for($i=0;$i<count($data);$i++)
   {
      $w=$this->widths[$i];
      if($w == 20 || $w == 10 || $w == 18){
        $algs = 'C';
      }else{
        $algs = 'L';
      }

      $a=isset($this->aligns[$i]) ? $this->aligns[$i] : $algs;
      $x=$this->GetX();
      $y=$this->GetY();
      $this->Rect($x,$y,$w,$h);
      $this->MultiCell($w,4,$data[$i],0,$a);
      $this->SetXY($x+$w,$y);
   }
   $this->Ln($h);
}
 
function CheckPageBreak($h)
{
   if($this->GetY()+$h>$this->PageBreakTrigger)
      $this->AddPage($this->CurOrientation);
}
 
function NbLines($w,$txt)
{
   $cw=&$this->CurrentFont['cw'];
   if($w==0)
      $w=$this->w-$this->rMargin-$this->x;
   $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
   $s=str_replace("\r",'',$txt);
   $nb=strlen($s);
   if($nb>0 and $s[$nb-1]=="\n")
      $nb--;
   $sep=-1;
   $i=0;
   $j=0;
   $l=0;
   $nl=1;
   while($i<$nb)
   {
      $c=$s[$i];
      if($c=="\n")
      {
         $i++;
         $sep=-1;
         $j=$i;
         $l=0;
         $nl++;
         continue;
      }
      if($c==' ')
         $sep=$i;
      $l+=$cw[$c];
      if($l>$wmax)
      {
         if($sep==-1)
         {
            if($i==$j)
               $i++;
         }
         else
            $i=$sep+1;
         $sep=-1;
         $j=$i;
         $l=0;
         $nl++;
      }
      else
         $i++;
   }
   return $nl;
}

// Cabecera de página
function Header()
{
    // Logo
    $this->Image('../../source/icrlogo.png',10,8,0,0,'PNG');
    // Font type
    $this->SetFont('Arial','B',7);
    // Mover a la derecha
    $this->cell(80);
    // Titulo
    $this->cell(30,10,'Jr. Gral. Jose de San Martin Mz. E Lote 6 Huachipa - Lurigancho Lima 15, Peru',0,0,'C',false);
    $this->cell(-40,18,'Central Telefonica: (511) 371-0443',0,2,'C',false);
    $this->cell(-40,-12,'E-mail: logistica@icrperusa.com',0,2,'C',false);
    $this->Ln(20);
}

function Cab(){
	$this->SetFont('Arial','',9);
  $cn = new PostgreSQL();
	$query = $cn->consulta("
    SELECT p.rucproveedor,p.razonsocial,p.direccion,a.paisnom,d.deparnom,r.provnom,i.distnom
    FROM admin.proveedor p INNER JOIN admin.pais a
            ON p.paisid=a.paisid
            INNER JOIN admin.departamento d
            ON p.departamentoid=d.departamentoid
            INNER JOIN admin.provincia r
            ON p.provinciaid=r.provinciaid
            INNER JOIN admin.distrito i
            ON p.distritoid=i.distritoid
  WHERE a.paisid LIKE d.paisid AND d.departamentoid LIKE r.departamentoid AND r.provinciaid LIKE i.provinciaid AND p.rucproveedor LIKE '".$this->ruc_."'");
	if ($cn->num_rows($query)>0) {
		while ($result = $cn->ExecuteNomQuery($query)) {
      $this->SetXY(15,38);
    	$this->cell(30,0,'RUC:                '.$result['rucproveedor'],0,0,'L',false);
      $this->SetXY(15,42);
    	$this->cell(10,0,'Razon Social:    '.$result['razonsocial'],0,0,'L',false);
      $this->SetXY(15,46);
		  $this->cell(10,0,'Direccion:         '.$result['direccion'],0,0,'L',false);
      $this->SetXY(15,50);
			$this->cell(20,0,'Distrito:             '.$result['distnom'],0,0,'L',false);
      $this->SetXY(15,54);
			$this->cell(4,0,'Provincia:         '.$result['provnom'],0,1,'L',false);
      $this->SetXY(15,58);
			$this->cell(4,0,'Departamento: '.$result['deparnom'],0,1,'L',false);
      $this->SetXY(15,62);
			$this->cell(4,0,'Pais:                 '.$result['paisnom'],0,0,'L',false);
		}
		$cn->close($query);
	}
	$this->SetFont('Arial','B',16);
  $this->SetXY(80,25);
  $this->cell(150,15,'Nro Solicitud de Cotizacion',0,0,'C',false);
  $this->SetXY(80,33);
	$this->cell(150,15,$this->nro_,0,0,'C');
}

function fnline()
{
  $this->SetFont('Arial','B',16);
  
  $this->cell(0,10,'_____________________________________________________________',0,1,'C',false);
}

//Body Pag
function FancyTable()
{
    // Colores, ancho de línea y fuente en negrita
    $this->SetFillColor(139,0,0);
    $this->SetTextColor(255);
    $this->SetDrawColor(169,169,169);
    $this->SetLineWidth(.1);
    $this->SetFont('','B',10);
    // Cabecera
    $header = array('Item','Descripcion','Medida','UND','Cantidad');
    $w = array(10, 82, 60, 18, 20);
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
    $this->Ln();
    // Restauración de colores y fuentes
    $this->SetFillColor(255,255,210);
    $this->SetTextColor(0);
    $this->SetFont('','',8);
    // Datos
    /*$fill = false;
    $cn = new PostgreSQL();
	$query = $cn->consulta("SELECT * FROM spconsultardetcotizacion('".$this->nro_."','".$this->ruc_."')");
		if ($cn->num_rows($query)>0) {
			$i = 1;
			$x = $this->GetX();
			while($row = $cn->ExecuteNomQuery($query)){
				$x = $this->GetX();
				$yBeforeCell = $this->GetY();
        		$this->Cell($w[0],6,$i++,'LTB','C',$fill);
        		$this->MultiCell($w[1],6,$row['matnom'],'TB','L',$fill);
        		$this->Cell($w[2],6,$row['matmed'],'TB','L',$fill);
        		$this->Cell($w[3],6,$row['matund'],'TB','C',$fill);
        		$this->Cell($w[4],6,number_format($row['cantidad']),'TBR','C',$fill);
        		$yCurrent = $this->GetY();
                $rowHeight = $yCurrent - $yBeforeCell;
                $this->SetXY($x + $col_widths[$col], $yCurrent - $rowHeight);
                $x = $this->GetX();
       	 		$this->Ln(6);
        		//$fill = !$fill;
    		}
    	}
    // Línea de cierre
    $this->Cell(array_sum($w),0,'','T');*/
}
//Pie de Pagina
function Footer()
{

	//Posicion a 1.,5 cm del final
	$this->SetY(-15);
	// Arial Italic 8
	$this->SetFont('Arial','I',8);
	$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
}
}

// Creación del objeto de la clase heredada
$pdf = new PDF();
$pdf->addprm($ruc,$nro);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->Cab();
$pdf->SetXY(10,57);
$pdf->fnline();
$pdf->SetXY(10,66);
$pdf->FancyTable();
$pdf->SetWidths(array(10, 82, 60, 18, 20));
$cn = new PostgreSQL();
$query = $cn->consulta("SELECT * FROM logistica.spconsultardetcotizacion('".$nro."','".$ruc."')");
	if ($cn->num_rows($query)>0) {
		$i = 1;
		while($fila = $cn->ExecuteNomQuery($query)){
			$pdf->Row(array($i++,$fila['matnom'], $fila['matmed'], $fila['matund'], $fila['cantidad']));
    	}
      $cn->close($query);
    }
$pdf->fnline();
$pdf->Output();
?>
