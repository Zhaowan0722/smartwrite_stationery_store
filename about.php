<?php

require_once('includes/config.php');

$page_title = "About Us - SmartWrite";
$show_sidebar = false;

include('includes/header.php');
?>

<style>


.about-hero {
    background:
        linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)),
        url('https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=1200&q=80');
    background-size: cover;
    background-position: center;
    color: white;
    text-align: center;
    padding: 100px 20px;
    border-radius: 10px;
    margin-bottom: 40px;
}

.about-hero h1 {
    font-size: 3.5rem;
    margin-bottom: 20px;
    color: white;
    border: none;
}

.about-hero p {
    font-size: 1.3rem;
    max-width: 800px;
    margin: 0 auto 30px;
    opacity: 0.95;
}

.story-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    align-items: center;
    margin-bottom: 60px;
    padding: 40px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.story-image {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.story-image img {
    width: 100%;
    height: 400px;
    object-fit: cover;
    transition: transform 0.5s;
}

.story-image:hover img {
    transform: scale(1.05);
}

.story-content h2 {
    color: #3498db;
    margin-bottom: 20px;
    font-size: 2.2rem;
}

.story-content p {
    color: #555;
    line-height: 1.8;
    margin-bottom: 20px;
    font-size: 1.1rem;
}

.values-section {
    margin-bottom: 60px;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.value-card {
    background: white;
    border-radius: 10px;
    padding: 40px 30px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s;
}

.value-card:hover {
    transform: translateY(-10px);
}

.value-icon {
    width: 80px;
    height: 80px;
    background: #ebf5fb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: #3498db;
    font-size: 2rem;
}

.value-card h3 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.4rem;
}

.value-card p {
    color: #666;
    line-height: 1.6;
}

.team-section {
    margin-bottom: 60px;
    background: white;
    border-radius: 10px;
    padding: 50px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.team-member {
    text-align: center;
}

.member-photo {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 20px;
    border: 5px solid #ebf5fb;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.member-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.team-member h3 {
    color: #333;
    margin-bottom: 5px;
    font-size: 1.3rem;
}

.member-role {
    color: #3498db;
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 1rem;
}

.member-desc {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.6;
}

.stats-section {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    border-radius: 10px;
    padding: 60px 40px;
    margin-bottom: 60px;
    color: white;
    text-align: center;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.stat-item h3 {
    font-size: 3rem;
    margin-bottom: 10px;
    font-weight: 800;
}

.stat-item p {
    font-size: 1.1rem;
    opacity: 0.9;
}

.testimonial-section {
    margin-bottom: 60px;
}

.testimonial-slider {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.testimonial-card {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    position: relative;
}

.testimonial-card::before {
    content: '"';
    position: absolute;
    top: 20px;
    left: 20px;
    font-size: 4rem;
    color: #3498db;
    opacity: 0.15;
    font-family: Georgia, serif;
}

.testimonial-text {
    color: #555;
    font-style: italic;
    line-height: 1.8;
    margin-bottom: 20px;
    font-size: 1.1rem;
    position: relative;
    z-index: 1;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 15px;
}

.author-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
}

.author-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.author-info h4 {
    margin: 0;
    color: #333;
}

.author-info p {
    margin: 5px 0 0;
    color: #666;
    font-size: 0.9rem;
}

.cta-section {
    text-align: center;
    padding: 60px 40px;
    background: #ebf5fb;
    border-radius: 10px;
    margin-bottom: 40px;
}

.cta-section h2 {
    color: #3498db;
    margin-bottom: 20px;
    font-size: 2.5rem;
}

.cta-buttons {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 30px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {

    .story-section {
        grid-template-columns: 1fr;
        padding: 20px;
    }

    .about-hero {
        padding: 60px 20px;
    }

    .about-hero h1 {
        font-size: 2.5rem;
    }

    .team-section {
        padding: 30px 20px;
    }

    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }

    .cta-buttons .btn {
        width: 100%;
        max-width: 300px;
    }

}
</style>

<div class="container">

    <!-- Hero Section -->
    <div class="about-hero">
        <h1>Our Story</h1>
        <p>
            From a simple idea to your trusted destination
            for quality stationery supplies.
        </p>
    </div>

    <!-- Our Story Section -->
    <div class="story-section">

        <div class="story-image">
            <img src="https://images.unsplash.com/photo-1506784365847-bbad939e9335?auto=format&fit=crop&w=800&q=80"
                 alt="SmartWrite Founder">
        </div>

        <div class="story-content">

            <h2>From Creative Ideas to Reality</h2>

            <p>
                Founded in 2026 by three passionate students
                - Zhao Wan, Tsui Hern, and Wen Liang -
                SmartWrite began as final year project
                with one clear mission:
                to provide affordable and reliable stationery
                for students, offices, and everyday users.
            </p>

            <p>
                They noticed many customers struggled to find
                quality notebooks, pens, files, and study tools
                in one convenient place. So they created
                SmartWrite to make stationery shopping easier,
                faster, and more organized.
            </p>

            <p>
                Starting from a small online concept,
                SmartWrite continues to grow while staying
                committed to quality products,
                fair pricing, and dependable service.
            </p>

        </div>

    </div>

    <!-- Our Values -->
    <div class="values-section">

        <h1 style="text-align:center; margin-bottom:20px;">
            Our Values
        </h1>

        <p style="
            text-align:center;
            color:#666;
            max-width:800px;
            margin:0 auto 40px;
            font-size:1.1rem;
        ">
            These principles guide everything we do
            at SmartWrite.
        </p>

        <div class="values-grid">

            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-pencil-alt"></i>
                </div>

                <h3>Quality Products</h3>

                <p>
                    We carefully select durable and practical
                    stationery items that meet the needs of
                    students, teachers, and professionals.
                </p>
            </div>

            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>

                <h3>Reliable Service</h3>

                <p>
                    We aim to provide a smooth shopping
                    experience with fast processing,
                    secure ordering, and trusted delivery.
                </p>
            </div>

            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-heart"></i>
                </div>

                <h3>Customer Care</h3>

                <p>
                    Every customer matters to us.
                    We believe great service builds
                    long-term trust and loyalty.
                </p>
            </div>

        </div>

    </div>

    <!-- Statistics -->
<div class="stats-section">

    <h2 style="color:white; margin-bottom:20px; font-size:2.2rem;">
        SmartWrite by the Numbers
    </h2>

    <p style="
        color:white;
        opacity:0.9;
        max-width:800px;
        margin:0 auto 40px;
        font-size:1.1rem;
    ">
        Supporting students, offices, and creative minds
        with trusted stationery solutions.
    </p>

    <div class="stats-grid">

        <div class="stat-item">
            <h3>10K+</h3>
            <p>Happy Customers</p>
        </div>

        <div class="stat-item">
            <h3>1+</h3>
            <p>Years of Growth</p>
        </div>

        <div class="stat-item">
            <h3>98%</h3>
            <p>Customer Satisfaction</p>
        </div>

        <div class="stat-item">
            <h3>500+</h3>
            <p>Products Available</p>
        </div>

    </div>

</div>

<!-- Meet the Team -->
<div class="team-section">

    <h1 style="text-align:center; margin-bottom:20px;">
        Meet Our Team
    </h1>

    <p style="
        text-align:center;
        color:#666;
        max-width:800px;
        margin:0 auto 40px;
        font-size:1.1rem;
    ">
        The people working behind SmartWrite.
    </p>

    <div class="team-grid">

        <div class="team-member">
            <div class="member-photo">
                <img src="images/kang.jpg" alt="Zhao Wan">
            </div>

            <h3>Zhao Wan</h3>
            <p class="member-role">Founder & CEO</p>

            <p class="member-desc">
                Oversees business strategy, customer growth,
                and ensures SmartWrite continues to deliver
                quality stationery products.
            </p>
        </div>

        <div class="team-member">
            <div class="member-photo">
                <img src="images/chan.jpeg" alt="Tsui Hern">
            </div>

            <h3>Tsui Hern</h3>
            <p class="member-role">Co-Founder & Product Manager</p>

            <p class="member-desc">
                Responsible for selecting useful products,
                managing inventory, and improving the
                customer shopping experience.
            </p>
        </div>

        <div class="team-member">
            <div class="member-photo">
                <img src="images/chin.jpeg" alt="Wen Liang">
            </div>

            <h3>Wen Liang</h3>
            <p class="member-role">Operations Manager</p>

            <p class="member-desc">
                Handles logistics, order processing,
                and keeps daily operations running
                smoothly and efficiently.
            </p>
        </div>

    </div>

</div>

<!-- Testimonials -->
<div class="testimonial-section">

    <h1 style="text-align:center; margin-bottom:20px;">
        What Our Customers Say
    </h1>

    <p style="
        text-align:center;
        color:#666;
        max-width:800px;
        margin:0 auto 40px;
        font-size:1.1rem;
    ">
        Feedback from people who trust SmartWrite.
    </p>

    <div class="testimonial-slider">

        <div class="testimonial-card">
            <p class="testimonial-text">
                "SmartWrite helped me get all my study
                essentials in one place. The ordering
                process was simple and delivery was fast."
            </p>

            <div class="testimonial-author">
                <div class="author-info">
                    <h4>Terry</h4>
                    <p>University Student</p>
                </div>
            </div>
        </div>

        <div class="testimonial-card">
            <p class="testimonial-text">
                "I regularly purchase office supplies here.
                Good product quality, fair pricing,
                and reliable service every time."
            </p>

            <div class="testimonial-author">
                <div class="author-info">
                    <h4>David </h4>
                    <p>Business Professional</p>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Call to Action -->
<div class="cta-section">

    <h2>Ready to Shop with SmartWrite?</h2>

    <p style="
        color:#666;
        max-width:600px;
        margin:0 auto;
        font-size:1.1rem;
    ">
        Discover quality stationery products trusted by
        students, offices, and everyday users.
    </p>

    <div class="cta-buttons">

        <a href="menu.php"
           class="btn"
           style="
                padding:15px 40px;
                font-size:1.1rem;
                background:#3498db;
           ">
            <i class="fas fa-shopping-cart"></i>
            Shop Now
        </a>

        <a href="contact.php"
           class="btn"
           style="
                padding:15px 40px;
                font-size:1.1rem;
                background:#6c757d;
           ">
            <i class="fas fa-envelope"></i>
            Contact Us
        </a>

    </div>

</div>
</div>

<?php

include('includes/footer.php');
?>