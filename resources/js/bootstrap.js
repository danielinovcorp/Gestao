import axios from "axios";

// Requisito para o Laravel identificar XHR
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Envia cookies de sessão (mesma origem)
axios.defaults.withCredentials = true;

// Lê o token do <meta name="csrf-token"> e envia no header correto
const token = document
  .querySelector('meta[name="csrf-token"]')
  ?.getAttribute("content");

if (token) {
  axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
}
