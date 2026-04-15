<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Asistencia</title>
    <style>
        /* ==========================================
           1. CONFIGURACIÓN DE PÁGINA Y FUENTES
           ========================================== */
        @page { 
            margin: 0.8cm; /* Margen ajustado para maximizar el área de impresión */
            size: a4 landscape; /* Hoja A4 en formato horizontal */
        }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 9px; 
            color: #333; 
        }
        
        /* ==========================================
           2. ENCABEZADO (Logo y Títulos)
           ========================================== */
        .header-container { 
            text-align: center; 
            margin-bottom: 20px; 
            position: relative; 
            min-height: 80px; 
        }
        /* Posiciona el logo absolutamente a la izquierda */
        .logo { 
            position: absolute; 
            left: 0; 
            top: 0; 
            width: 100px; 
        } 
        .title-green { 
            color: #166534; /* Verde corporativo */
            font-size: 18px; 
            font-weight: bold; 
            margin: 0; 
            text-transform: uppercase; 
        }
        .subtitle { 
            color: #4b5563; 
            font-size: 12px; 
            margin-top: 5px; 
            font-weight: bold; 
        }
        
        /* ==========================================
           3. ESTRUCTURA DE LA TABLA
           ========================================== */
        /* table-layout: fixed; obliga a la tabla a respetar nuestros porcentajes */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; 
        }
        th, td { 
            border: 1px solid #000; 
            text-align: center; 
            vertical-align: middle; 
        }
        thead th { 
            background-color: #f8fafc; 
            padding: 4px 0; 
        }
        
        /* ==========================================
           4. ANCHOS DE COLUMNAS (Basados en % para exactitud)
           ========================================== */
        .col-num { width: 2.5%; }
        
        /* Nombres: Ocupan el 32% del ancho, no saltan de línea y ocultan lo que desborde */
        .col-nombre { 
            width:15%; 
            text-align: left; 
            padding-left: 5px; 
            font-size: 10px; 
            white-space: nowrap; 
            overflow: hidden; 
        }
        
        .col-dni { width: 6%; }
        
        /* Días: Cada día ocupa el 1.7% (1.7% * 31 = ~52.7% del total) */
        .col-dia { width: 1.7%; } 
        
        /* Observaciones: Toma el resto del espacio disponible (6.8%) */
        .col-obs { width: 6.8%; }

        /* ==========================================
           5. ESTILOS DE ESTADOS Y DOMINGOS
           ========================================== */
        .bg-domingo { background-color: #e5e7eb !important; } /* Sombreado gris */
        .letra-D { color: #6b7280; font-weight: normal; } /* 'D' de descanso */
        .letra-F { color: #dc2626; font-weight: bold; }   /* 'F' de falta en rojo */
        .letra-T { color: #ea580c; font-weight: bold; }   /* 'T' de tardanza en naranja */

        /* ==========================================
           6. NOTA INFERIOR
           ========================================== */
        .footer-note { 
            margin-top: 22px; 
            font-size: 10px; 
            text-align: left; 
            font-weight: bold; 
            color: #000; 
        }
    </style>
</head>
<body>
    
    <div class="header-container">
        <img src="{{ public_path('img/logo_reporte.png') }}" class="logo" alt="Logo Amazon Nuts">

        <h1 class="title-green">FORMATO DE ASISTENCIA AMAZON NUTS SAC</h1>
        <p class="subtitle">ASISTENCIA MES DE {{ mb_strtoupper(str_replace(' ', ' DEL ', $mes)) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="col-num">N°</th>
                <th rowspan="2" class="col-nombre">APELLIDOS Y NOMBRES</th>
                <th rowspan="2" class="col-dni">DNI</th>
                @foreach($dias as $dia)
                    <th class="col-dia {{ $dia->dayOfWeek == 0 ? 'bg-domingo' : '' }}">
                        {{ $dia->format('j') }}
                    </th>
                @endforeach
                <th rowspan="2" class="col-obs">OBS.</th>
            </tr>
            
            <tr>
                @php $letras = [1=>'L', 2=>'M', 3=>'M', 4=>'J', 5=>'V', 6=>'S', 0=>'D']; @endphp
                @foreach($dias as $dia)
                    <th class="col-dia {{ $dia->dayOfWeek == 0 ? 'bg-domingo' : '' }}">
                        {{ $letras[$dia->dayOfWeek] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        
        <tbody>
            @foreach($matriz as $index => $fila)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="col-nombre">{{ mb_strtoupper($fila['nombre']) }}</td>
                    <td>{{ $fila['dni'] }}</td>
                    
                    @foreach($dias as $dia)
                        @php
                            $fechaStr = $dia->format('Y-m-d');
                            $esDomingo = ($dia->dayOfWeek == 0);
                            $letra = $fila['asistencias'][$fechaStr] ?? '';
                            
                            // Si no hay asistencia registrada y es domingo, ponemos 'D'
                            $mostrar = $letra;
                            if ($letra === '-' || $letra === '') {
                                $mostrar = $esDomingo ? 'D' : '';
                            }
                        @endphp
                        
                        <td class="{{ $esDomingo ? 'bg-domingo' : '' }} letra-{{ $letra === '' && $esDomingo ? 'D' : $letra }}">
                            {{ $mostrar }}
                        </td>
                    @endforeach
                    
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-note">
        NOTA: LOS PERMISOS SOLO SE DAN EN CASO DE EMERGENCIA O POR SALUD DEL TRABAJADOR.
    </div>

</body>
</html>