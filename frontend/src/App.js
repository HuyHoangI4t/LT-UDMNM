import React from 'react';
import 'bootstrap/dist/css/bootstrap.min.css';
import ChatAi from './components/ChatAi';
import Header from './components/Header';
// import Footer from './components/Footer';

function App() {
  return (
    <div className="App">
      <Header />
      <ChatAi />
      {/* <Footer /> */}
    </div>
  );
}

export default App;