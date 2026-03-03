import EventEmitter from 'eventemitter3'

const eventBus = new EventEmitter()
window.wc_greenpayEventBus = eventBus

export default eventBus
