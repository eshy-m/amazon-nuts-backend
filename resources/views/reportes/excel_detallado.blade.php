<table>
    <thead>
        <tr>
            <th colspan="8" style="text-align: center; color: #166534; font-size: 16px; font-weight: bold;">
                FORMATO DE ASISTENCIA DETALLADO - AMAZON NUTS SAC
            </th>
        </tr>
        <tr>
            <th colspan="8" style="text-align: center; color: #4b5563; font-size: 12px; font-weight: bold;">
                PERIODO: {{ $inicio }} AL {{ $fin }}
            </th>
        </tr>
        <tr><th colspan="8"></th></tr>
        <tr style="background-color: #f8fafc; font-weight: bold; border: 1px solid #000;">
            <th style="border: 1px solid #000;">DNI</th>
            <th style="border: 1px solid #000;">TRABAJADOR</th>
            <th style="border: 1px solid #000;">ÁREA</th>
            <th style="border: 1px solid #000;">FECHA</th>
            <th style="border: 1px solid #000;">INGRESO</th>
            <th style="border: 1px solid #000;">SALIDA</th>
            <th style="border: 1px solid #000;">HORAS TRABAJADAS</th>
            <th style="border: 1px solid #000;">ESTADO</th>
        </tr>
    </thead>
    <tbody>
        @foreach($asistencias as $a)
        <tr>
            <td style="border: 1px solid #000;">{{ $a->trabajador->dni }}</td>
            <td style="border: 1px solid #000;">{{ $a->trabajador->nombres }} {{ $a->trabajador->apellidos }}</td>
            <td style="border: 1px solid #000;">{{ $a->trabajador->area }}</td>
            <td style="border: 1px solid #000;">{{ $a->fecha }}</td>
            <td style="border: 1px solid #000;">{{ $a->hora_ingreso }}</td>
            <td style="border: 1px solid #000;">{{ $a->hora_salida ?? '---' }}</td>
            <td style="border: 1px solid #000; font-weight: bold; text-align: center;">{{ $a->horas_trabajadas ?? '0.00' }}</td>
            <td style="border: 1px solid #000; color: {{ $a->estado == 'Falta' ? '#dc2626' : '#000' }};">
                {{ $a->estado }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>