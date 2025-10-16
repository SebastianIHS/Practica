-- Crear tabla de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  total DECIMAL(10,2) NOT NULL,
  estado VARCHAR(50) DEFAULT 'completado' NOT NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Crear tabla de detalles de pedido
CREATE TABLE IF NOT EXISTS detalle_pedido (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Insertar algunos datos de prueba (si no existen)
-- Primero verificamos si ya existen pedidos
INSERT INTO pedidos (usuario_id, total, estado)
SELECT 1, 15000, 'completado'
WHERE NOT EXISTS (SELECT 1 FROM pedidos LIMIT 1);

-- Obtenemos el ID del pedido recién insertado o el primero si ya existían
SET @pedido_id = LAST_INSERT_ID();
IF @pedido_id = 0 THEN
  SELECT id INTO @pedido_id FROM pedidos LIMIT 1;
END IF;

-- Insertamos algunos detalles de pedido de prueba
INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
SELECT @pedido_id, 1, 2, 5000, 10000
WHERE NOT EXISTS (SELECT 1 FROM detalle_pedido LIMIT 1) AND EXISTS (SELECT 1 FROM productos WHERE id = 1);

INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
SELECT @pedido_id, 2, 1, 5000, 5000
WHERE EXISTS (SELECT 1 FROM productos WHERE id = 2) AND NOT EXISTS (SELECT 1 FROM detalle_pedido WHERE pedido_id = @pedido_id AND producto_id = 2);