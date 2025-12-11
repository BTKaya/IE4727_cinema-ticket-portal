# Cinema Ticket Booking Web Portal

A full-stack web application for online cinema ticket booking, developed collaboratively as part of a semester-long university project. This project demonstrates front-end and back-end development without external frameworks, including dynamic seat selection, shopping cart functionality, and email ticketing.

## Features
- **User Authentication:** Register and login to manage bookings.
- **Movie Listing:** Browse movies on the homepage with featured movies displayed prominently.
- **Seat Booking System:**
  - Dynamic seating view: available (green), selected (yellow), booked/held (red).
  - Automatic release of held seats after 10 minutes.
- **Shopping Cart:** Manage multiple bookings before checkout.
- **Ticket & Receipt Delivery:** PDF receipt and tickets sent via HTML-formatted email.
- **Movie Details:** View full movie summary, details, and seat availability.
- **Search Functionality:** Search for movies by title.

## Tech Stack
- **Front-end:** HTML, CSS, JavaScript (no frameworks)
- **Back-end:** PHP
- **Database:** MySQL
- **Other Tools:** Email integration for ticketing

## Setup / Installation
1. Clone this repository:

```bash
git clone https://github.com/yourusername/IE4727_cinema-ticket-portal.git
cd IE4727_cinema-ticket-portal
```

2. Set up a local server environment (e.g., XAMPP as used for this project)
3. Import the provided database.sql file into your MySQL server to create the required database and tables
4. Update db.php with your MySQL credentials.
5. Start your local server and navigate to [http://localhost/IE4727_cinema-ticket-portal](http://localhost/IE4727_cinema-ticket-portal) in your browser.
6. Register a new account to start booking.

## Screenshots / Demo

## Contribution / Credits
This project was collaboratively developed with Nepexe (https://github.com/Nepexe), with contributions to both front-end and back-end functionality.

## License
This project is for educational purposes.
