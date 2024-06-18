<?php
// Defina as informações das impressoras HP LaserJet Flow E82660
$printers['ADM']['ip'] = '10.44.3.10';
$printers['CON']['ip'] = '10.44.3.11';
$printers['DIR']['ip'] = '10.44.3.12';
$printers['BIB']['ip'] = '10.44.3.13';
$printers['GMG']['ip'] = '10.44.3.14';
$printers['GSA']['ip'] = '10.44.3.15';
$printers['SHR']['ip'] = '10.44.3.16';
$printers['GCl']['ip'] = '10.44.3.17';
$printers['GPb']['ip'] = '10.44.3.18';

//$a = snmp2_walk("10.44.3.17", "public", "");
//print_r($a);
//die();

$community = "public";

// OID para obter o número total de impressões
$total_prints_oid = '1.3.6.1.2.1.43.10.2.1.4.1.1';
// OID para obter o número de impressões em cores
$color_prints_oid = '1.3.6.1.2.1.43.11.1.1.9.1.1';
//$color_prints_oid = '1.3.6.1.2.1.43.11.1.1.9.1.1';
//$color_prints_oid = '1.3.6.1.4.1.11.2.3.9.4.2.1.3.1.7';
$serial_number_oid = '1.3.6.1.2.1.43.5.1.1.17.1';

// OIDs para obter contadores de páginas monocromáticas, coloridas e totais
//$oidMonoPages =  '1.3.6.1.2.1.43.10.2.1.4.1.1'; // Total de páginas monocromáticas
//$oidColorPages = '1.3.6.1.2.1.43.10.2.1.4.1.2'; // Total de páginas coloridas
//$oidTotalPages = '1.3.6.1.2.1.43.10.2.1.4.1.3'; // Total de páginas (monocromáticas + coloridas)


// Função para fazer uma consulta SNMP
function snmp_get($host, $community, $oid) {
    //$result = snmpget($host, $community, $oid);
    $result = snmp2_get($host, $community, $oid); 
    return $result;
}

foreach ($printers as $local=>$printer) {
    $ip = $printer['ip'];
    
    // Obtém o número de série da impressora
    $serial = snmp_get($ip, $community, $serial_number_oid);
    // Obtém o número total de impressões
    $total_prints = snmp_get($ip, $community, $total_prints_oid);
    // Obtém o número de impressões em cores
    $color_prints = snmp_get($ip, $community, $color_prints_oid);

    if ($total_prints !== false && $color_prints !== false) {
        echo "Imp $local $serial $ip - Total: $total_prints, Coloridas: $color_prints\n";
    } else {
        echo "Falha ao obter informações da impressora $ip.\n";
    }
}

