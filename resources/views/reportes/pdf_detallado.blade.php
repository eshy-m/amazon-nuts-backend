<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Detallado de Asistencia</title>
    <style>
        @page { margin: 1cm; size: a4 landscape; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { color: #166534; font-size: 18px; font-weight: bold; }
        .subtitle { color: #666; font-size: 12px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f3f4f6; color: #374151; font-weight: bold; padding: 8px; border: 1px solid #d1d5db; text-align: center; }
        td { padding: 6px; border: 1px solid #d1d5db; text-align: center; }
        .text-left { text-align: left; }
        
        .tardanza { color: #ea580c; font-weight: bold; }
        .falta { color: #dc2626; font-weight: bold; }
        .asistencia { color: #16a34a; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">AMAZON NUTS SAC</div>
        <div class="subtitle">REPORTE DETALLADO DE ASISTENCIA</div>
        <div style="margin-top: 5px;">Periodo: {{ $inicio }} al {{ $fin }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>DNI</th>
                <th class="text-left">TRABAJADOR</th>
                <th>ÁREA</th>
                <th>FECHA</th>
                <th>INGRESO</th>
                <th>SALIDA</th>
                <th>HORAS</th>
                <th>ESTADO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($asistencias as $index => $asistencia)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $asistencia->trabajador->dni }}</td>
                <td class="text-left">{{ mb_strtoupper($asistencia->trabajador->nombres . ' ' . $asistencia->trabajador->apellidos) }}</td>
                <td>{{ $asistencia->trabajador->area }}</td>
                <td>{{ \Carbon\Carbon::parse($asistencia->fecha)->format('d/m/Y') }}</td>
                <td>{{ $asistencia->hora_ingreso ?? '--:--' }}</td>
                <td>{{ $asistencia->hora_salida ?? '--:--' }}</td>
                <td style="font-weight: bold;">{{ $asistencia->horas_trabajadas ?? '0.00' }}</td>
                <td class="{{ strtolower($asistencia->estado) }}">
                    {{ strtoupper($asistencia->estado) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 8px; color: #999;">
        Reporte generado el: {{ now()->format('d/m/Y H:i:s') }}
    </div>

</body>
</html>