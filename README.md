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
git clone https://github.com/BTKaya/IE4727_cinema-ticket-portal.git
cd IE4727_cinema-ticket-portal
```

2. Set up a local server environment (e.g., XAMPP as used for this project)
3. Import the provided database.sql file into your MySQL server to create the required database and tables
4. Update db.php with your MySQL credentials.
5. Start your local server and navigate to [http://localhost/IE4727_cinema-ticket-portal](http://localhost/IE4727_cinema-ticket-portal) in your browser.
6. Register a new account to start booking.

## Screenshots / Demo

Home Page
![Lumina Home Page](https://github.com/user-attachments/assets/9e665023-73ad-4eda-a810-c29fd7e39c14)

Movie Booking
![Movie Booking](https://github.com/user-attachments/assets/bca6c52a-f05b-4197-ad37-491784c87a10)


Ticket Example
<img width="936" height="486" alt="Ticket Example" src="https://github.com/user-attachments/assets/1510ba87-0eb7-4db2-b7a4-cc0cf6103343" />


Receipt Example
<img width="490" height="585" alt="Receipt Example" src="https://github.com/user-attachments/assets/8930ec1b-6624-464a-8d65-e89e5665714c" />

## Contribution / Credits
This project was collaboratively developed with Nepexe (https://github.com/Nepexe), with contributions to both front-end and back-end functionality.

## License
This project is for educational purposes.
