import React from 'react';
import 'bootstrap/dist/css/bootstrap.min.css';
import './scss/style.scss';
import ChatAi from './components/ChatAi';
import Header from './components/Header';
import Body from './components/Body';
import Footer from './components/Footer';
import AdmissionPage from './page/AdmissionPage';

function App() {
  return (
    <div className="App">
      <Header />
      <Body />
      <main>
        <AdmissionPage />
      </main>
      <ChatAi />
      <Footer />

    </div>
  );
}

export default App;