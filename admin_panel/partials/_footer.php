

  <script src="../assets/js/logout.js"></script>
<!-- OLD jQuery - <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js" type="text/javascript"></script> -->
<!-- NEW jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <script src="js/bootstrap.bundle.js"></script>
    
    <script src="../js/script.js"></script>
    <!-- <script src="../js/oranbyte-google-translator.js"></script> -->
    <script>
     
      function switchToTab(tabId) {
        if (event) {
        event.preventDefault();
      }
    
        var tab = new bootstrap.Tab(document.getElementById(tabId));
        tab.show();
       
      }
    
      
     
      function isTabAvailable(tabId) {
        return document.getElementById(tabId) !== null;
      }
      
      document.addEventListener('DOMContentLoaded', function () {
        var hash = window.location.hash;
        if (hash) {
          var tabId = hash.substring(1);
          if (isTabAvailable(tabId)) {
            switchToTab(tabId);
          } else {
            window.location.href = window.location.href.split('#')[0];
          }
        }
      });
     
    </script>

   
</body>

</html>

