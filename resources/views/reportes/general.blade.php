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
            margin: 0.8cm; 
            size: a4 landscape; 
        }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 10px; /* Tamaño de texto solicitado */
            color: #333; 
        }
        
        /* ==========================================
           2. ENCABEZADO
           ========================================== */
        .header-container { 
            text-align: center; 
            margin-bottom: 20px; 
            position: relative; 
            min-height: 80px; 
        }
        .logo { 
            position: absolute; 
            left: 0; 
            top: 0; 
            width: 100px; 
        } 
        .title-green { 
            color: #166534; 
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
           3. ESTRUCTURA DE LA TABLA (Layout Fijo)
           ========================================== */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; 
        }
        th, td { 
            border: 1px solid #000; 
            text-align: center; 
            vertical-align: middle; 
            padding: 2px 0;
        }
        thead th { 
            background-color: #f8fafc; 
        }
        
        /* ==========================================
           4. DISTRIBUCIÓN DE COLUMNAS (Total 100%)
           ========================================== */
        .col-num { width: 3%; }
        
        /* Nombre al 15% como solicitaste */
        .col-nombre { 
            width: 25%; 
            text-align: left; 
            padding-left: 5px; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }
        
        .col-dni { width: 8%; }
        
        /* Los días ahora tienen más espacio (aprox 2.15% cada uno) */
        .col-dia { width: 2.15%; } 
        
        .col-obs { width: 7.35%; }

        /* ==========================================
           5. ESTADOS Y COLORES
           ========================================== */
        .bg-domingo { background-color: #e5e7eb !important; } 
        .letra-D { color: #6b7280; } 
        .letra-F { color: #dc2626; font-weight: bold; }   
        .letra-T { color: #ea580c; font-weight: bold; }   

        .footer-note { 
            margin-top: 15px; 
            font-size: 10px; 
            text-align: left; 
            font-weight: bold; 
            color: #000; 
        }
    </style>
</head>
<body>
    
    <div class="header-container">
        <img src="{{ public_path('img/logo_reporte.png') }}" class="logo" alt="Logo">
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
                            $mostrar = ($letra === '-' || $letra === '') ? ($esDomingo ? 'D' : '') : $letra;
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