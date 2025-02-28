</div> <!-- /.main-content -->

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="row">
        <div class="col-md-4 mb-4 mb-md-0">
          <h5>Système de Réservation</h5>
          <p class="text-light opacity-75">Simplifiez vos rendez-vous avec notre plateforme de réservation en ligne intuitive et sécurisée.</p>
          <div class="social-links">
            <a href="#" class="me-2"><i class="bi bi-facebook"></i></a>
            <a href="#" class="me-2"><i class="bi bi-twitter"></i></a>
            <a href="#" class="me-2"><i class="bi bi-instagram"></i></a>
            <a href="#" class="me-2"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>
        <div class="col-md-4 mb-4 mb-md-0">
          <h5>Liens rapides</h5>
          <ul>
            <li><a href="index.php">Accueil</a></li>
            <li><a href="calendrier.php">Calendrier</a></li>
            <li><a href="contact.php">Contact</a></li>
            <?php if (!isset($_SESSION['user_id'])) : ?>
              <li><a href="connexion.php">Connexion</a></li>
              <li><a href="inscription.php">Inscription</a></li>
            <?php else : ?>
              <li><a href="profil.php">Mon profil</a></li>
              <li><a href="annulation_rdv.php">Mes rendez-vous</a></li>
            <?php endif; ?>
          </ul>
        </div>
        <div class="col-md-4">
          <h5>Contact</h5>
          <address>
            <p><i class="bi bi-geo-alt me-2"></i> 123 Rue du Rendez-vous, 75000 Paris</p>
            <p><i class="bi bi-telephone me-2"></i> +33 1 23 45 67 89</p>
            <p><i class="bi bi-envelope me-2"></i> contact@reservation-system.com</p>
          </address>
        </div>
      </div>
      <hr class="mt-4 mb-3 border-secondary">
      <div class="row">
        <div class="col-md-6 text-center text-md-start">
          <p class="mb-0">&copy; <?php echo date('Y'); ?> Système de Réservation. Tous droits réservés.</p>
        </div>
        <div class="col-md-6 text-center text-md-end">
          <p class="mb-0">
            <a href="#">Politique de confidentialité</a> | 
            <a href="#">Conditions d'utilisation</a>
          </p>
        </div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom JavaScript -->
  <script src="../resources/js/script.js"></script>
</body>
</html>