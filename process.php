<?php
include 'dbConnection.php'; // Include the database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['submit1'])) {  // Borrow book form
        // Retrieve form data
        $studentName = filter_var($_POST['studentName'], FILTER_SANITIZE_STRING);
        $studentID = filter_var($_POST['studentID'], FILTER_SANITIZE_STRING);
        $bookID = filter_var($_POST['books'], FILTER_SANITIZE_STRING);
        $borrowDate = $_POST['borrowDate'];
        $token = $_POST['token'];
        $returnDate = $_POST['returnDate'];
        $fees = $_POST['fees'];
        $paid = $_POST['paid'];

        $date1 = strtotime($borrowDate); 
        $date2 = strtotime($returnDate);

        if (file_exists("./token.json")) {
            $jsonData = json_decode(file_get_contents("./token.json"), true);

            if (isset($jsonData[0]['token'])) {
                $inputToken = (int)$_POST['token']; 
                $tokens = $jsonData[0]['token'];    

                if (in_array($inputToken, $tokens)) 
                    $flagToken = 1;
                else 
                    $flagToken = 0;
            } 
            if (isset($jsonData[0]['usedToken']) && $flagToken == 1)
                $flagUsedToken = 0;

            if (isset($jsonData[0]['usedToken'])) {
                $inputToken = (int)$_POST['token']; 
                $tokens = $jsonData[0]['usedToken'];    

                if (in_array($inputToken, $tokens)) 
                    $flagUsedToken = 1;
                else 
                    $flagUsedToken = 0;
            } else 
                $flagUsedToken = -1;
        }

        if ($date1 == $date2) {
            echo "You're not allowed to return the book on the same day.";
            return;
        }

        if ($date1 > $date2) {
            echo "Invalid return date.";
            return;
        }

        if ($flagUsedToken != 0) {
            echo "You cannot use this token because it has already been used.";
            return;
        }

        // 1 day = 86400 seconds
        if (($date2 - $date1) > 10 * 86400 && $flagToken != 1) {
            echo "<div class='message error'>You're not allowed to borrow the book for more than 10 days. Days: " . (($date2 - $date1) / 86400) . "</div>";
            return;
        }

        // Validation for fields
        if (!preg_match("/[A-Za-z\-]/", $_POST['studentName'])) {
            echo "<div class='message error'>Invalid Name</div>";
            return;
        }
        if (!preg_match("/\d{2}-\d{5}-\d{1}/", $studentID)) {
            echo "<div class='message error'>Invalid Student ID</div>";
            return;
        }
        if (!preg_match("/[0-9]/", $_POST['fees'])) {
            echo "<div class='message error'>Invalid Fees</div>";
            return;
        }
        if ($bookID == "book0") {
            echo "<div class='message error'>Please select a book</div>";
            return;
        }

        // Get book details
        $sql = "SELECT id, bookTitle, bookQuantity FROM book WHERE bookTitle = '$bookID'";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $availableQuantity = $row['bookQuantity'];
            $bookTitle = $row['bookTitle'];

            if ($availableQuantity <= 0) {
                echo "<div class='message error'>Book is not available.</div>";
                return;
            }
        }

        // Cookie to prevent borrowing the same book again
        $cookieName = str_replace(['=', ',', ';', ' ', "\t", "\r", "\n", "\013", "\014"], '_', $bookTitle);
        if (isset($_COOKIE[$cookieName])) {
            if ($_COOKIE[$cookieName] == $studentName) {
                echo "<div class='message error'>You're not allowed to borrow the same book again.</div>";
                return;
            }
        }

        setcookie($cookieName, $studentName, time() + (10 * 24 * 60 * 60), "/");

        // Update the token JSON if the token is used
        if (file_exists("./token.json") && $flagToken == 1) {
            $jsonData = json_decode(file_get_contents("./token.json"), true) ?: [];
            if (!isset($jsonData[0]['usedToken']) || !is_array($jsonData[0]['usedToken'])) 
                $jsonData[0]['usedToken'] = [];

            if (!in_array($token, $jsonData[0]['usedToken'])) 
                $jsonData[0]['usedToken'][] = $token;

            file_put_contents("./token.json", json_encode($jsonData, JSON_PRETTY_PRINT));
        }

        // Decrement book quantity
        $newQuantity = $availableQuantity - 1;
        $updateSQL = "UPDATE book SET bookQuantity = '$newQuantity' WHERE bookTitle = '$bookID'";
        if (!mysqli_query($conn, $updateSQL)) {
            echo "Error updating book quantity.";
            return;
        }

        // Display the submitted data
        echo "<div class='details'>";
        echo "<h2>Details:</h2>";
        echo "<p><strong>Student Full Name:</strong> " . $studentName . "</p>";
        echo "<p><strong>Student ID:</strong> " . $studentID . "</p>";
        echo "<p><strong>Book Title:</strong> " . $bookTitle . "</p>";
        echo "<p><strong>Borrow Date:</strong> " . $borrowDate . "</p>";
        echo "<p><strong>Token:</strong> " . $token . "</p>";
        echo "<p><strong>Return Date:</strong> " . $returnDate . "</p>";
        echo "<p><strong>Fees:</strong> $" . $fees . "</p>";
        echo "</div>";

    } 
    else if (isset($_POST['submit2'])) {  // Add/update book form
        $bookTitle = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
        $bookAuthor = filter_var($_POST['authorName'], FILTER_SANITIZE_STRING);
        $isbn = filter_var($_POST['isbn'], FILTER_SANITIZE_STRING);
        $bookCategory = filter_var($_POST['category'], FILTER_SANITIZE_STRING);
        $bookQuantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT);

        // Validate inputs
        if (!preg_match("/[A-Za-z\-]/", $bookTitle)) {
            echo "<div class='message error'>Invalid Book Title</div>";
            return;
        }
        if (!preg_match("/[A-Za-z\-]/", $bookAuthor)) {
            echo "<div class='message error'>Invalid Book Author</div>";
            return;
        }
        if (!preg_match("/[A-Za-z\-]/", $bookCategory)) {
            echo "<div class='message error'>Invalid Book Category</div>";
            return;
        }
        if (!preg_match("/[0-9]/", $isbn) || strlen($isbn) != 13) {
            echo "<div class='message error'>Invalid ISBN</div>";
            return;
        }
        if (!preg_match("/[0-9]/", $bookQuantity)) {
            echo "<div class='message error'>Invalid Book Quantity</div>";
            return;
        }

        // Insert new book into database
        $sql = "SELECT * FROM book WHERE bookTitle = '$bookTitle' AND isbn = '$isbn'";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            echo "<div class='message error'>Book already exists in the database.</div>";
            return;
        }

        $sql = "INSERT INTO book (bookTitle, bookAuthor, isbn, bookCategory, bookQuantity) 
                VALUES ('$bookTitle', '$bookAuthor', '$isbn', '$bookCategory', '$bookQuantity')";
        if (mysqli_query($conn, $sql)) {
            echo "<div class='message success'>New book added successfully.</div>";
        } else {
            echo "<div class='message error'>Error adding book: " . mysqli_error($conn) . "</div>";
        }
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_book'])) {
        $bookID = filter_var($_POST['book_id'], FILTER_SANITIZE_NUMBER_INT);
        $bookTitle = filter_var($_POST['bookTitle'], FILTER_SANITIZE_STRING);
        $bookAuthor = filter_var($_POST['bookAuthor'], FILTER_SANITIZE_STRING);
        $isbn = filter_var($_POST['isbn'], FILTER_SANITIZE_STRING);
        $bookCategory = filter_var($_POST['bookCategory'], FILTER_SANITIZE_STRING);
        $bookQuantity = filter_var($_POST['bookQuantity'], FILTER_SANITIZE_NUMBER_INT);
    
        // SQL to update book information
        $updateSQL = "UPDATE book SET bookTitle = '$bookTitle', bookAuthor = '$bookAuthor', isbn = '$isbn', bookCategory = '$bookCategory', bookQuantity = '$bookQuantity' WHERE id = '$bookID'";
    
        if (mysqli_query($conn, $updateSQL)) {
            echo "Book updated successfully.";
        } else {
            echo "Error updating book: " . mysqli_error($conn);
        }
    }
    
    // Delete the book
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_book'])) {
        $bookID = filter_var($_POST['book_id'], FILTER_SANITIZE_NUMBER_INT);
    
        // SQL to delete the book
        $deleteSQL = "DELETE FROM book WHERE id = '$bookID'";
        
        if (mysqli_query($conn, $deleteSQL)) {
            $successMessage = "Book deleted successfully.";
        } else {
            $errorMessage = "Error deleting book: " . mysqli_error($conn);
        }
    }
}
    ?>
