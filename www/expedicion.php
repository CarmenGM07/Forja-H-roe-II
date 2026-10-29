<?php
/**
 * =====================================================================
 *  expedicion.php · Forja de Héroes II · La party y la mazmorra
 * =====================================================================
 *  0613 DWES · UD3 · Tarea de refuerzo (sin nota)
 *  Autor/a: Copilot · Variante: VARIANTE B
 *
 *  Estructura OBLIGATORIA (como en el paso 09 del caso guiado):
 *   1) strict_types y carga de inc/reglas.php y del fichero de datos
 *   2) CÁLCULOS: aquí arriba, sin HTML
 *   3) PRESENTACIÓN: HTML con sintaxis alternativa
 *      (foreach (...): ... endforeach;   if (...): ... else: ... endif;)
 *
 *  AHORA SÍ puedes usar if, switch, match, bucles y arrays.
 *  Todavía NO: funciones propias ni formularios (UD4).
 *
 *  R8 · Comenta cada bloque explicando QUÉ hace y POR QUÉ.
 *       Usa al menos una vez //, # y /* ... *\/.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/reglas.php';
require_once __DIR__ . '/inc/datos-' . VARIANTE . '.php';

# R2 · Cálculo de cada héroe y del total de la party.
$poderTotal = 0;
$heroesActivos = 0;
$nivelmedio = 0;
$danioParty = 0;
$rondas = [];
$resultado = 0;
$motivo = 0;

$vidaMaxParty = array_sum(array_column($party,'vidaMax'));
$vidaParty= array_sum(array_column($party, 'vidaMax'));

foreach ($party as $i => $heroe) {
    $nivel = intdiv((int) $heroe['xp'], XP_POR_NIVEL) + 1;
    $vidaMax = VIDA_BASE + (int) $heroe['constitucion'] * $nivel * MULT_VIDA[$heroe['clase']];
    $pctVida = ($heroe['vida'] / $vidaMax) * 100;

    $danio = match ($heroe['clase']) {
        'Guerrera' => ($heroe['fuerza'] / 10) + ($nivel * 2),
        'Hechicero' => ($heroe['inteligencia'] / 10) + ($nivel * 2.5),
        'Pícara' => ($heroe['destreza'] / 10) + ($nivel * 1.5),
        'Clérigo' => ($heroe['inteligencia'] * 1.5) * $nivel,
    };

  
# R2.8 · Daño total y líder de la party, ignorando a los héroes K.O.
$danio=0;
$heroes=0;
$lider = null;

foreach ($party as $heroe){
    if($heroe['estado'] === 'K.O'){
        continue;
    }

    $danio + $heroe['danio'];
    $heroes++;

    if($lider === null || $heroe['poder'] > $lider['poder']){
        $lider = $heroe;
    }
}

// =====================================================================
// R4 · LA EXPEDICIÓN (sala a sala)
// =====================================================================
# R4.1 · Estado inicial del recorrido.
$oro = $oroInicial;
$xpGanada = 0;
$dario= [];
$vidaParty = $vidaParty;
$salasExploradas = 0;

$hayPicaria = in_array('Picara' , array_column($party, 'clase'),true);
$hayClerigo = in_array ('Clérigo', array_column($party, 'clase'),true);


# R4.2 · Recorremos cada sala y aplicamos sus efectos en el orden indicado.
foreach($salas as $n => $sala){
    //Esta función que va a aparecer a continuación, cuenta la sala como explorada al entrar, aunque la party
    //caiga en ella.
    $salasExploradas++;

    //Más tarde, ahora en esta variable, el tipo de sala decide el efecto.
    switch($sala['tipo']){
        case 'vacia' :
            $diario[] = 'Sala' . ($n + 1) . '.' . $sala['nombre'] . 'vacia, la party sigue avanzando.';
            break;

        case 'trampa':
            //Con la variable Picaria se evita; si no, la party pierde 'valor' de vida.
            if($hayPicaria){
                $diario [] = 'Sala' . ($n + 1) . '.' . $sala['nombre'] . 'trampa evitada gracias a la Picaria';
            }else{
                $vidaParty = $sala['valor'];
                 $diario [] = 'Sala' . ($n + 1) . '.' . $sala['nombre'] . '¡TRAMPA!' . $sala['valor'] . 'de vida ';
            }

            break;

        case 'combate':
            //Más tarde, pierde intdiv (Valor,4) de vida y gana 'valor' de XP.
            $vidaParty -= intdiv($sala['valor'], 4 );
            $xpGanada += $sala['valor'];
            $diario[] = 'Sala' . ($n + 1). '.' . $sala['nombre']. 'combate, - '. intdiv($sala['valor'], 4). 'de vida y + '. $sala['valor']. 'XP';
            break;
        
        case 'tesoro': 
            //A continuación vamos a realizar la suma 'valor' al oro y, si hay 'objeto', vamos a añadir 1 unidad al inventario.
            $oro +=$sala['valor'];
            if(isset($sala['valor'])){
                $inventario[$sala['objeto']] = ($inventario[$sala['objeto']] ?? 0) + 1;
                $diario[] = 'Sala' . ($n + 1) . '.' . $sala['nombre']. '+' . $sala['nombre'] . '+' .$sala['valor']. $sala['objeto']. '>>';
            }else{
                $diario[] = 'Sala' . ($n + 1). '.' .$sala['nombre']. '+' .$sala['valor']. '>>'; 
            }
            break;

        case 'descanso':
            //Cura 'valor' (el doble con Clérigo) sin pasar de la vida máxima de la party.
            if($hayClerigo){
                $vidaParty = min($vidaParty + $sala['valor'] * 2, array_sum(array_column($party, 'vidaMax' )));
                $diario[] = 'Sala '. ($n + 1) . '.' . $sala['nombre']  . ':descanso, +' . ($sala['valor'] * 2). ' de vida (bendicion del Clérigo)';  
            }else{
                $vidaParty = min($vidaParty + $sala['nombre']. ':descanso, + ' . array_sum(array_column($party,'vidaMax')));
                $diario[] = 'Sala' . ($n + 1 ). '.' . $sala['nombre'] . ':descanso, +'. $sala['valor']. 'de vida.';

            }
            break;
    }

    //Ahora vamos a realizar un if, para ver si la vida llega a 0 o menos: se deja en 0, se anota y se sale del recorrido.
    if($vidaParty <= 0){
        $vidaParty = 0;
        $diario[] = 'La party ha caído y la expedición termina aquí.';
        break;
    }
}

//$n contiene la posición de esa sala dentro del array

// =====================================================================
// R5 · EL JEFE
// =====================================================================
# R5.1 · Determinamos si la llave está en el inventario y cuánto daño puede hacer la party.
$tieneLlave = array_key_exists($jefe['llave'], $inventario);
$golpeParty = (int) round($danio);

// =====================================================================
// R4 (cont.) · INVENTARIO FINAL
// =====================================================================
# R4.5 · Calculamos el valor total del inventario y ordenamos por abundancia.

// =====================================================================
// R6 · EL MAPA
// =====================================================================
# R6.1 · Recuento de caracteres para comprobar que el mapa está bien construido.
$valorinventario = 0;
foreach($inventario as $objeto => $cantidad){
    $valorinventario += $cantidad * $precios[$objeto];

}

//Ahora, voy a realizar la suma de todas las cantidades.
$numeroObjeto = array_sum($inventario);

//Ordenaciín de mayor a menor cantidad.
$inventarioOrdenado = $inventario;
arsort($inventarioOrdenado);

// El más abundante, es el primero de la copia ordenada
$objetomasabundante = array_key_first ($inventarioOrdenado);

# R6.3 · Barra de vida global de la party.
//Primero, voy a poner el porcentaje de vida final de la party respecto a su vida máxima
$pctVidaParty = $vidaParty / array_sum(array_column ($party, 'VidaMax')) * 100;

//Luego, voy  a rellenar los bloques (bloques llenos de la barra), con el porcentaje sobre
//BLOQUS_BARRA, redondeado a entero.
$bloquesllenos = (int)round($pctVida / 100 * BLOQUES_BARRA);


//Ahora, voy a crear la barra con el for.
$barraparty = "";
    for($b = 0; $b < BLOQUES_BARRA; $b++){
        if($b < $bloquesllenos){
            $barraparty = '█';
        }else{
            $barraparty = '░';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expedición · <?=  htmlspecialchars('Las Minas de Hierro Negro') ?>· Forja de Héroes II</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<main>
    <span class="insignia"> <?=  htmlspecialchars('Las Minas de Hierro Negro') ?>Las Minas de Hierro Negro</span>
    <h1> <?=  htmlspecialchars('Las Minas de Hierro Negro') ?>Las Minas de Hierro Negro</h1>
    <p class="subtitulo">Informe de expedición · líder: Las Minas de Hierro Negro</p>

    <h2>La party</h2>
    <table>
        <tr><th>Héroe</th><th>Clase</th><th>Nivel</th><th>Vida</th><th>Daño</th><th>Poder</th><th>Posición</th><th>Estado</th></tr>
        <!-- TODO R7: una fila por héroe con foreach: ... endforeach;
             Estado en <td class="peligro"> si es K.O. o En peligro, y en
             <td class="vida"> en otro caso (if: ... else: ... endif;) -->
                <?php foreach($party as $heroe):?>

        <tr><td><?= htmlspecialchars($heroe['nombre']) ?></td>
        <td><?= htmlspecialchars($heroe['clase'])?></td>
        <td><?=  $heroe['nivel'] ?></td>
            <td><span class="barra mini"><?=  number_format($heroe['danio'],1,',','.') ?></span> vida/vidaMax</td>
            <td><?=  htmlspecialchars($heroe['posicion']) ?></td>
            <td><?php if ($heroe['estado'] === 'K.O' || $heroe['estado'] === 'En peligro'): ?></td>
            <td class="peligro"><?= htmlspecialchars(($heroe['estado'])) ?></td>
            <?php else: ?>
            <td class ="vida"> <?= htmlspecialchars($heroe['estado']) ?></td>
                <?php endif;?>
        </tr>
        <?php endforeach;?>
    </table>

    <div class="rejilla">
        <div class="stat"><div class="etq">Poder total</div><div class="val"><?= $poderTotal?></div></div>
        <div class="stat"><div class="etq">Nivel medio</div><div class="val"><?= number_format($nivelmedio,1,',','.' )?></div></div>
        <div class="stat"><div class="etq">Daño por ronda</div><div class="val"><?= number_format($danioParty,1,',','.')?></div></div>
        <div class="stat"><div class="etq">Héroes activos</div><div class="val"><?=  $heroesActivos ?> / <?= count ($party) ?></div></div>
    </div>

    <h2>Diario de la expedición</h2>
    <ol class="diario">
        <!-- TODO R7: un <li> por línea del diario -->
            <?php foreach ($diario as $linea): ?>
                <li> <?htmlspecialchars($linea)?> </li>
                <?php endforeach; ?>
    </ol>
    <p>Salas exploradas: 
    <?php 
        $totalSalas = count($salas);
        $salasPendientes = $totalSalas - $salasExploradas;
    ?>

    <?= $salasExploradas ?> de <?= $totalSalas ?>
        <!-- TODO: solo si quedan salas sin explorar, mostrar cuántas en .peligro -->
         <?php if ($salasPendientes > 0): ?> 
            .<span class="peligro"> Salas pendientes: <?=  $salasPendientes ?></span>
         <?php endif; ?>
        · XP ganada: <?= $xpGanada ?></p>

    <h2>El jefe: <?= htmlspecialchars($jefe['nombre']) ?></h2>
    <p>Vida <?= $jefe['vida'] ?> golpe de la party: <?= $golpeParty ?></p>
    <!-- TODO R7: la tabla de rondas solo se muestra si hubo combate -->
     <?php if (count($rondas) > 0):?>
    <table>
        <tr><th>Ronda</th><th>Vida del jefe</th><th>Vida de la party</th></tr>
        <?php foreach ($rondas as $ronda): ?>
           <tr>
                <td> <?=  $ronda['ronda'] ?> </td>
                <td> <?= $ronda['ronda'] ?> </td>
                <td> <?= $ronda['vidaParty'] ?> </td>
           </tr> 
        <?php endforeach; ?>
    
    </table>

     <?php endif; ?>
     <?php if ($resultado === 'Victoria'): ?>
     <p class="resultado  vida"><strong><?= htmlspecialchars($resultado) ?></strong></p> 
     <?php endif; ?>

    <p class="barra vida">PARTY <?=  $barraparty ?> <?=  $vidaParty ?> / <?= $vidaMaxParty ?></p>

    <h2>Inventario</h2>
    <table>
        <tr><th>Objeto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr>
        <!-- TODO R7: una fila por objeto del inventario ORDENADO -->
         <?php foreach ($inventarioOrdenado as $objeto => $cantidad): ?>
            <tr>
                <td> <?=  htmlspecialchars($objeto) ?></td>
                <td> <?= $cantidad ?>
                <td> <?= number_format($precios[$objeto], 2, ',' , '.') ?></td>
                <td><?= number_format($cantidad * $precios[$objeto], 2,',', '.') ?> </td>
            </tr>
            <?php endforeach;?>
    </table>
    <div class="rejilla">
        <div class="stat"><div class="etq">Objetos</div><div class="val">TODO</div></div>
        <div class="stat"><div class="etq">Valor del inventario</div><div class="val">TODO mo</div></div>
        <div class="stat"><div class="etq">Oro final</div><div class="val">TODO mo</div></div>
        <div class="stat"><div class="etq">Más abundante</div><div class="val peq">TODO</div></div>
    </div>

    <h2>Mapa de la mazmorra</h2>
    <!-- TODO R7: tabla con foreach anidados (filas y celdas). Cada <td> lleva
         la clase CSS de LEYENDA_MAPA[$celda][0] y el carácter dentro. -->
    <table class="mapa">
        <?php foreach($mapa as $fila): ?>
            <tr>
                <?php foreach($fila as $celda): ?>
                    <td class="<?=  LEYENDA_MAPA[$celda][0] ?>" ><?= htmlspecialchars($celda) ?> </td>
                    <?php endforeach; ?>
            </tr>
        <?php endforeach;?>
    </table>
    

    <!-- TODO: leyenda con el recuento de cada carácter -->
    <!-- TODO: mensaje de coherencia del mapa (if: else: endif;) -->
    <p>
        <?php foreach (LEYENDA_MAPA as $simbolo => $datosLeyenda):?>
            <?=  htmlspecialchars($simbolo) ?> <?=  $datosLeyenda[1] ?> : <?=  $conteoCeldas[$simbolo] ?? 0 ?>
        <?php endforeach; ?>
    </p>

    <?php if (($conteoCeldas['S'] ?? 0) === $totalSalas): ?>
        
        <p class="vida"> Mapa coherente: hay una cinco por cada sala (<?= $totalSalas?>). ?></p>
        <?php else: ?>
            <p class="peligro"> Mapa incoherente: hay <?=  $conteoCeldas['S'] ?? 0 ?> S y <?= $totalSalas ?> Salas</p>
        <?php endif ;?>
    <footer>Informe generado el TODO con PHP TODO · cada recarga lo recalcula todo desde cero.</footer>
</main>
</body>
</html>