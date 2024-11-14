-- Create Reservation Types table for better data organization and referential integrity
CREATE TABLE ReservationType (
    type_id INT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    description TEXT,
    CONSTRAINT unique_type_name UNIQUE (type_name)
);

-- Create Destinations table for consistent reference
CREATE TABLE Destination (
    destination_id INT PRIMARY KEY,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    region VARCHAR(50),
    CONSTRAINT unique_city_country UNIQUE (city, country)
);

-- Create Agents table
CREATE TABLE Agent (
    agent_id INT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    hire_date DATE NOT NULL,
    CONSTRAINT unique_email UNIQUE (email)
);

-- Create main Reservations table
CREATE TABLE Reservation (
    reservation_id INT PRIMARY KEY,
    type_id INT NOT NULL,
    destination_id INT NOT NULL,
    agent_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    booking_date DATE NOT NULL,
    travel_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'CONFIRMED',
    cost DECIMAL(10,2) NOT NULL,
    revenue DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_type FOREIGN KEY (type_id) REFERENCES ReservationType(type_id),
    CONSTRAINT fk_destination FOREIGN KEY (destination_id) REFERENCES Destination(destination_id),
    CONSTRAINT fk_agent FOREIGN KEY (agent_id) REFERENCES Agent(agent_id),
    CONSTRAINT valid_status CHECK (status IN ('CONFIRMED', 'CANCELLED', 'COMPLETED', 'PENDING')),
    CONSTRAINT valid_dates CHECK (travel_date >= booking_date)
);

-- Sample data insertion
INSERT INTO ReservationType (type_id, type_name, description) VALUES
(1, 'Flight', 'Air travel bookings'),
(2, 'Hotel', 'Accommodation bookings'),
(3, 'Package', 'Combined flight and hotel packages');

INSERT INTO Destination (destination_id, city, country, region) VALUES
(1, 'Paris', 'France', 'Europe'),
(2, 'Tokyo', 'Japan', 'Asia'),
(3, 'New York', 'USA', 'North America');

INSERT INTO Agent (agent_id, first_name, last_name, email, hire_date) VALUES
(1, 'John', 'Smith', 'john.smith@travel.com', '2023-01-15'),
(2, 'Maria', 'Garcia', 'maria.garcia@travel.com', '2023-03-20');

INSERT INTO Reservation VALUES
(1, 1, 1, 1, 'Alice Brown', '2024-01-01', '2024-06-15', 'CONFIRMED', 500.00, 600.00),
(2, 2, 1, 1, 'Bob Wilson', '2024-01-02', '2024-06-16', 'CONFIRMED', 800.00, 950.00),
(3, 3, 2, 2, 'Carol Davis', '2024-01-03', '2024-07-01', 'CANCELLED', 1500.00, 1800.00);

-- Query 1: Filter by reservation type for revenue analysis
SELECT 
    rt.type_name,
    COUNT(*) as booking_count,
    SUM(r.revenue) as total_revenue,
    AVG(r.revenue) as avg_revenue
FROM Reservation r
JOIN ReservationType rt ON r.type_id = rt.type_id
WHERE r.status != 'CANCELLED'
GROUP BY rt.type_name
ORDER BY total_revenue DESC;

-- Query 2: Aggregate costs by destination
SELECT 
    d.city,
    d.country,
    COUNT(*) as booking_count,
    SUM(r.cost) as total_cost,
    SUM(r.revenue - r.cost) as total_profit
FROM Reservation r
JOIN Destination d ON r.destination_id = d.destination_id
WHERE r.status != 'CANCELLED'
GROUP BY d.city, d.country
ORDER BY total_cost DESC;

-- Query 3: Update booking statuses for cancellations
UPDATE Reservation 
SET status = 'CANCELLED',
    revenue = 0  -- Assuming no revenue for cancellations
WHERE reservation_id = :reservation_id;

-- Query 4: Join with AgentData for booking performance
SELECT 
    a.first_name || ' ' || a.last_name as agent_name,
    COUNT(*) as total_bookings,
    SUM(r.revenue) as total_revenue,
    SUM(r.revenue - r.cost) as total_profit,
    COUNT(CASE WHEN r.status = 'CANCELLED' THEN 1 END) as cancellations,
    ROUND(COUNT(CASE WHEN r.status = 'CANCELLED' THEN 1 END) * 100.0 / COUNT(*), 2) as cancellation_rate
FROM Reservation r
JOIN Agent a ON r.agent_id = a.agent_id
GROUP BY a.agent_id, a.first_name, a.last_name
ORDER BY total_revenue DESC;