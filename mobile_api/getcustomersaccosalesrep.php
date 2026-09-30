<?php 
require_once('../connection/db.php');

$employeeId=$_POST['employeeId'];

$tax = 0;
$vat_cus = false;

$taxQuery = "SELECT `rate` FROM `tbl_tax` LIMIT 1";
$taxResult = $conn->query($taxQuery);

if ($taxResult && $taxResult->num_rows > 0) {
    $taxRow = $taxResult->fetch_assoc();
    $tax = $taxRow['rate'];
}

$sql="SELECT * FROM `tbl_customer` WHERE `status`='1' AND `blacklist`='0' AND `ref`='$employeeId'";
$result = mysqli_query($conn, $sql);

$customerarray = array();

while ($row = mysqli_fetch_array($result)) {
    if($row['vat_num'] != '') {
        $tax_rate = $tax;
        $vat_cus = true;
    } else {
        $tax_rate = 0;
        $vat_cus = false;
    }

    array_push($customerarray, array("id" => $row['idtbl_customer'], "name" => $row['customer'], "nic" => $row['nic'], "phone" => $row['phone'], "email" => $row['email'], "address" => $row['address'], "areaId" => $row['tbl_area_idtbl_area'],"vat_cus" => $vat_cus, "tax_rate" => $tax_rate,"longitude" => $row['longitude'], "latitude" => $row['latitude']));
}

echo json_encode($customerarray);
?>
