<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Asistencia Consolidado</title>
    <style>
        /* ==========================================
           TU DISEÑO ORIGINAL PRESERVADO
           ========================================== */
        @page { 
            margin: 0.8cm; 
            size: a4 landscape; 
        }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 10px; 
            color: #333; 
        }
        
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
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
        }
        th, td { 
            border: 1px solid #000; 
            text-align: center; 
            padding: 4px 2px; 
        }
        
        /* Encabezados */
        .bg-header { background-color: #f3f4f6; font-weight: bold; }
        .col-nombre { text-align: left; padding-left: 5px; width: 220px; }
        .col-dia { width: 18px; font-size: 9px; }
        
        /* Colores de estados (Tus estilos) */
        .bg-domingo { background-color: #e5e7eb; }
        .letra-P { color: #15803d; font-weight: bold; } /* Presente */
        .letra-T { color: #c2410c; font-weight: bold; } /* Tardanza */
        .letra-F { color: #b91c1c; font-weight: bold; } /* Falta */
        .letra-D { color: #6b7280; } /* Domingo */

        /* Firmas */
        .footer-signatures { 
            margin-top: 50px; 
            width: 100%; 
        }
        .signature-box { 
            width: 30%; 
            text-align: center; 
            display: inline-block; 
            vertical-align: top;
        }
        .line { 
            border-top: 1px solid #000; 
            width: 80%; 
            margin: 0 auto 5px auto; 
        }

        .footer-note {
            margin-top: 20px;
            font-style: italic;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>

    <div class="header-container">
        <div class="title-green">REPORTE CONSOLIDADO DE ASISTENCIA</div>
        <div class="subtitle">
            PERIODO: {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr class="bg-header">
                <th rowspan="2">N°</th>
                <th rowspan="2" class="col-nombre">APELLIDOS Y NOMBRES</th>
                <th rowspan="2">DNI</th>
                <th colspan="{{ count($dias) }}">DÍAS DEL MES</th>
                <th rowspan="2">OBS.</th>
            </tr>
            <tr class="bg-header">
                @php $letras = ['D','L','M','M','J','V','S']; @endphp
                @foreach($dias as $dia)
                    <th class="col-dia {{ $dia->dayOfWeek == 0 ? 'bg-domingo' : '' }}">
                        {{ $dia->day }}<br>
                        <small>{{ $letras[$dia->dayOfWeek] }}</small>
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
                            $estado = $fila['asistencias'][$fechaStr] ?? '';
                            
                            // Mapeo de letra según estado
                            $letra = '';
                            if($estado == 'Presente') $letra = 'P';
                            elseif($estado == 'Tardanza') $letra = 'T';
                            elseif($estado == 'Falta') $letra = 'F';
                            
                            $mostrar = ($letra === '' && $esDomingo) ? 'D' : $letra;
                        @endphp
                        <td class="{{ $esDomingo ? 'bg-domingo' : '' }} letra-{{ $mostrar }}">
                            {{ $mostrar }}
                        </td>
                    @endforeach
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-note">
        <strong>LEYENDA:</strong> P: Presente | T: Tardanza | F: Falta | D: Domingo / Descanso.
    </div>

    <div class="footer-signatures">
        <div class="signature-box">
            <div class="line"></div>
            CONTABILIDAD
        </div>
        <div class="signature-box">
            <div class="line"></div>
            RECURSOS HUMANOS
        </div>
        <div class="signature-box">
            <div class="line"></div>
            GERENCIA GENERAL
        </div>
    </div>

</body>
</html>