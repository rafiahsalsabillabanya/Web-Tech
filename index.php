<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Book Store</title>
    <link rel="stylesheet" type="text/css" href="css/style.css" />
</head>

<body>
    <img src="css/ID.png" alt="Book Store Logo">
    <div class="container">
        <!-- Top 3 boxes -->
        <div class="boxTop">
            <p>Box 1</p>
            <?php
            include 'dbConnection.php';
            // Query to fetch all books from the database
            $sql = "SELECT * FROM book";
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Book Title</th><th>Author</th><th>ISBN</th><th>Category</th><th>Quantity</th></tr>";

                // Loop through the rows and display the book details
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['bookTitle'] . "</td>";
                    echo "<td>" . $row['bookAuthor'] . "</td>";
                    echo "<td>" . $row['isbn'] . "</td>";
                    echo "<td>" . $row['bookCategory'] . "</td>";
                    echo "<td>" . $row['bookQuantity'] . "</td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p>No books found in the database.</p>";
            }
            ?>
        </div>

        <div class="boxSecTop">
            <p>Search Book by ID</p>
            <!-- Form to search for book by ID -->
            <form method="POST" action="">
                <input type="number" name="book_id" placeholder="Enter Book ID" required>
                <input type="submit" name="search_book" value="Search">
            </form>

            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>

            <?php
            if (isset($_POST['search_book'])) {
                // Prepared Statement to prevent SQL Injection
                $stmt = $conn->prepare("SELECT * FROM book WHERE id = ?");
                $stmt->bind_param("i", $_POST['book_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    // Fetch the book details from the query result
                    $book = $result->fetch_assoc();
            ?>
                    <p>Box 2</p>
                    <h3>Book Information</h3>
                    <form method="POST" action="process.php">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>" required>
                        <label>Book Title:</label>
                        <input type="text" name="bookTitle" value="<?php echo $book['bookTitle']; ?>" required>
                        <label>Author:</label>
                        <input type="text" name="bookAuthor" value="<?php echo $book['bookAuthor']; ?>" required>
                        <label>ISBN:</label>
                        <input type="text" name="isbn" value="<?php echo $book['isbn']; ?>" required>
                        <label>Category:</label>
                        <input type="text" name="bookCategory" value="<?php echo $book['bookCategory']; ?>" required>
                        <label>Quantity:</label>
                        <input type="number" name="bookQuantity" value="<?php echo $book['bookQuantity']; ?>" required>

                        <input type="submit" name="edit_book" value="Edit Book">
                    </form>
            <?php } ?>
            <!-- Delete Book Button -->
            <form method="POST" action="process.php">
                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                <input type="submit" name="delete_book" value="Delete Book" onclick="return confirm('Are you sure you want to delete this book?')">
            </form>
            <?php
            }
            ?>
            <?php
            if (isset($successMessage)) { echo "<p class='success'>$successMessage</p>"; }
            if (isset($errorMessage)) { echo "<p class='error'>$errorMessage</p>"; }
            ?>
        </div>
        <div class="box1">
        <div class="box11">
            <p>Box 3</p>
            <h3>All Tokens</h3>
            <ul>
                <?php
                $tokenFile = "./token.json";
                if (file_exists($tokenFile)) {
                    $jsonData = json_decode(file_get_contents($tokenFile), true);

                    if (isset($jsonData[0]['token'])) 
                    {
                        foreach ($jsonData[0]['token'] as $token) 
                            echo "<li>Token: $token</li>";
                    } 
                    else 
                        echo "<li>No tokens found in the JSON file.</li>";
                    
                } 
                else 
                    echo "<li>JSON file not found.</li>";
                
                ?>
            </ul>
        </div>
        <div class="box11">
        <h3>Used Tokens</h3>
            <ul>
                <?php
                if (file_exists($tokenFile)) {
                    $jsonData = json_decode(file_get_contents($tokenFile), true);

                    if (isset($jsonData[0]['usedToken'])) 
                    {
                        foreach ($jsonData[0]['usedToken'] as $token) 
                            echo "<li>Token: $token</li>";
                    } 
                    else 
                        echo "<li>No tokens found in the JSON file.</li>";
                    
                } 
                else 
                    echo "<li>JSON file not found.</li>";
                
                ?>
            </ul>
            </div>

            </div>

        <div class="box2">
            <p>Box 4.1</p>
            <img src = "assets/1.jpg">
        </div>
        <div class="box2">
            <p>Box 4.2</p>
            <img src = "assets/2.jpg">
        </div>
        <div class="box2">
            <p>Box 4.3</p>
            <img src = "assets/3.jpg">
        </div>
        <div class="box3">
            <div class="form-container">
                <h2>Borrow a Book</h2>
                <form method="POST" action="process.php">
                    <input type="text" placeholder="Student Full Name" name="studentName" required>
                    <input type="text" placeholder="Student ID" name="studentID" required>
                    <select name="books" id="books" required>
                        <option value="book0">Select a book</option>
                        <option value="Introduction to Programming">Introduction to Programming</option>
                        <option value="Data Structure">Data Structure</option>
                        <option value="Straight Line">Straight Line</option>
                        <option value="Integration">Integration</option>
                        <option value="Algorithms">Algorithms</option>
                    </select>
                    <label>Borrow Date</label>
                    <input type="date" placeholder="Borrow Date" name="borrowDate" required>
                    <input type="text" placeholder="Token" name="token" required>
                    <label>Return Date</label>
                    <input type="date" placeholder="Return Date" name="returnDate" required>
                    <input type="text" placeholder="Fees" name="fees" required>

                    <label>Paid: </label>
                    <input type="radio" id="paid" name="paid" value="Paid" required>
                    <label for="paid">Yes</label>
                    <input type="radio" id="not_paid" name="paid" value="Not Paid" required>
                    <label for="not_paid">No</label>
                    <br>

                    <input type="submit" name="submit1" value="Submit">
                </form>
            </div>
        </div>
        <div class="box4">
            <div class="form-container">
                <h2>Book Info</h2>
                <form action="process.php" method="post">
                    <input type="text" placeholder="Title" name="title">
                    <input type="text" placeholder="Author Name" name="authorName">
                    <input type="text" placeholder="ISBN" name="isbn">
                    <input type="text" placeholder="Category" name="category">
                    <input type="text" placeholder="Quantity" name="quantity">
                    <input type="submit" name="submit2" value="Submit">
                </form>
            </div>
        </div>
    </div>
</body>

</html>
