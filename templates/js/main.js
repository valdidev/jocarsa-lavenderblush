function makeDraggable(el) {
  let offsetX = 0,
    offsetY = 0;
  let isDragging = false;

  el.addEventListener("mousedown", (e) => {
    isDragging = true;
    offsetX = e.clientX - el.getBoundingClientRect().left;
    offsetY = e.clientY - el.getBoundingClientRect().top;
    el.style.cursor = "grabbing";
    el.style.zIndex = 999999;
  });

  document.addEventListener("mousemove", (e) => {
    if (!isDragging) return;
    el.style.left = e.clientX - offsetX + "px";
    el.style.top = e.clientY - offsetY + "px";
  });

  document.addEventListener("mouseup", () => {
    isDragging = false;
    el.style.cursor = "grab";
    el.style.zIndex = 1;
  });
}

function getClasses() {
  const articles = document.querySelectorAll("article.draggable");
  let result = [];
  articles.forEach((a) => {
    const className = a.querySelector(".nombre")?.textContent.trim() || "Clase";
    const props = [];
    a.querySelectorAll(".propiedades ul li").forEach((li) => {
      props.push(li.textContent.trim());
    });
    const mets = [];
    a.querySelectorAll(".metodos ul li").forEach((li) => {
      mets.push(li.textContent.trim());
    });

    const xPos = parseInt(a.style.left, 10) || 250;
    const yPos = parseInt(a.style.top, 10) || 250;

    result.push({
      className: className,
      properties: props,
      methods: mets,
      x: xPos,
      y: yPos,
    });
  });
  return result;
}

function listClasses() {
  console.log(getClasses());
}

function saveClasses() {
  const data = getClasses();
  fetch("index.php?ajax=save_classes", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
  })
    .then((r) => r.text())
    .then((msg) => {
      alert(msg);
      console.log(msg);
    })
    .catch((err) => console.error("Error saving classes:", err));
}

function loadClasses() {
  fetch("index.php?ajax=load_classes")
    .then((r) => r.json())
    .then((data) => {
      if (Array.isArray(data)) {
        document
          .querySelectorAll("article.draggable")
          .forEach((a) => a.remove());
        data.forEach((cls) => {
          const tpl = document.getElementById("article-template");
          const clone = tpl.content.cloneNode(true);
          const article = clone.querySelector("article");

          article.querySelector(".nombre").textContent = cls.className;

          const ulProps = article.querySelector(".propiedades ul");
          ulProps.innerHTML = "";
          (cls.properties || []).forEach((p) => {
            const li = document.createElement("li");
            li.textContent = p;
            ulProps.appendChild(li);
          });

          const ulMets = article.querySelector(".metodos ul");
          ulMets.innerHTML = "";
          (cls.methods || []).forEach((m) => {
            const li = document.createElement("li");
            li.textContent = m;
            ulMets.appendChild(li);
          });

          article.style.left = (cls.x || 250) + "px";
          article.style.top = (cls.y || 250) + "px";

          document.querySelector("main").appendChild(article);
          makeDraggable(article);
        });
      } else if (data.error) {
        console.warn(data.error);
      }
    })
    .catch((err) => console.error("Error loading classes:", err));
}

document.addEventListener("DOMContentLoaded", () => {
  const addBtn = document.getElementById("addBtn");
  const listBtn = document.getElementById("listBtn");
  const saveBtn = document.getElementById("saveBtn");
  const main = document.querySelector("main");

  addBtn.addEventListener("click", (e) => {
    e.preventDefault();
    const tpl = document.getElementById("article-template");
    const clone = tpl.content.cloneNode(true);
    const article = clone.querySelector("article");
    main.appendChild(article);
    makeDraggable(article);
  });

  listBtn.addEventListener("click", (e) => {
    e.preventDefault();
    listClasses();
  });

  saveBtn.addEventListener("click", (e) => {
    e.preventDefault();
    saveClasses();
  });

  loadClasses();
});
