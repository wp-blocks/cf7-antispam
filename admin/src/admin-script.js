// save on ctrl-s keypress
if (document.body.classList.contains('contact_page_wpcf7-antispam')) {
  document.addEventListener('keydown', e => {
    if (e.ctrlKey && e.key === 's') {
      e.preventDefault();
      document.getElementById('submit').click();
    }
  });
}
