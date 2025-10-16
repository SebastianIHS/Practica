-- Crear tabla de pedidos
DROP TABLE IF EXISTS detalle_pedido;
DROP TABLE IF EXISTS pedidos;

-- Verificar si la tabla usuarios existe
SET @tabla_existe = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'practicas' AND table_name = 'usuarios');

-- Crear tabla de pedidos sin restricción de clave foránea inicialmente
CREATE TABLE pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  total DECIMAL(10,2) NOT NULL,
  estado VARCHAR(50) DEFAULT 'completado' NOT NULL,
  INDEX idx_usuario_id (usuario_id)
) ENGINE=InnoDB;

-- Crear tabla de detalles de pedido
CREATE TABLE detalle_pedido (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;