<!--### Header Part ##################################################-->
<?php
include '../template/header.php';
?>
<style>
    td{
        font-size: 12px;
    }
</style>
<!--#####################################################-->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Add 'show' class to the element with ID "collapseLayouts2"
    $(document).ready(function() {
        $("#collapsePages_report").addClass("show");
        $("#mastera").addClass("show");
       // $("#masterb").addClass("show");
        $("#Master").addClass("active bg-success text-white");
    });
</script>



<!--#####################################################-->

        <div class="container-fluid px-4">
            <!--body#####################################################-->
           
            
            <!----------------------------------------------------------->
            <b><span class="text-success">Master Report</span></b>
            <form action="master/master.php" method="post">

                <label for="start_date">Start Date:</label>
                <input type="date" name="start_date" required>

                <label for="end_date">End Date:</label>
                <input type="date" name="end_date" required>

    
                <button type="submit">Submit</button>

            </form>

<!-------------------------------------------------------------------------->

<!------------------------------------------------------------------------>

<!----------------------------------------------------------------------------------->
          




            <!--#####################################################-->
        </div>
    </main>


<!--###### Footer Part ###############################################-->
<?php
include '../template/footer.php';
?>
<!--#####################################################-->