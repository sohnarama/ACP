function afficherConfirmation() {
  if (confirm("Êtes-vous sûr de vouloir envoyer ce formulaire ?")) {
      return true; // Envoyer le formulaire si l'utilisateur clique sur OK
  } else {
      return false; // Ne pas envoyer le formulaire si l'utilisateur clique sur Annuler
  }
}
document.addEventListener('DOMContentLoaded', function() {
  console.log("JavaScript loaded!"); // Vérifiez que ce message apparaît dans la console

  // Code d'écoute des clics
});
console.log("JavaScript loaded!"); 

document.addEventListener('DOMContentLoaded', (event) => {
  document.querySelectorAll('.change-status-btn').forEach(button => {
      button.addEventListener('click', function() {
          const userId = this.getAttribute('data-user-id');
          const currentStatus = parseInt(this.getAttribute('data-current-status'));

          // Déterminer le texte de l'action (activer ou désactiver)
          const actionText = currentStatus === 1 ? 'désactiver' : 'activer';
          document.getElementById('statusActionText').textContent = actionText;

          // Mettre à jour les champs cachés dans le formulaire
          document.getElementById('statusUserIdInput').value = userId;
          document.getElementById('newStatusInput').value = currentStatus === 1 ? 0 : 1;
      });
  });
});