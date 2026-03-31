<!--estamos haciendo el diseño del correo-->
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eaeaea; border-radius: 10px; }
        .header { background-color: #166534; color: white; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; background-color: #f9fafb; }
        .label { font-weight: bold; color: #15803d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Novedades en Amazon Nuts</h2>
        </div>
        <div class="content">
            <p>Hola, tienes una nueva solicitud de cotización desde la página web:</p>
            
            <p><span class="label">👤 Cliente:</span> {{ $contactMessage->sender_name }}</p>
            <p><span class="label">🏢 Empresa:</span> {{ $contactMessage->company }}</p>
            <p><span class="label">✉️ Correo:</span> {{ $contactMessage->email }}</p>
            <p><span class="label">🌎 País:</span> {{ $contactMessage->country }}</p>
            <p><span class="label">🌰 Producto de Interés:</span> {{ strtoupper($contactMessage->product_interest) }}</p>
            
            <hr style="border: 0; border-top: 1px solid #ccc; margin: 20px 0;">
            
            <p><span class="label">📝 Detalles del requerimiento:</span></p>
            <p style="background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd;">
                {{ $contactMessage->message }}
            </p>

            <p style="margin-top: 30px; font-size: 12px; color: #666; text-align: center;">
                Ingresa al Panel de Administración para gestionar este prospecto.
            </p>
        </div>
    </div>
</body>
</html>